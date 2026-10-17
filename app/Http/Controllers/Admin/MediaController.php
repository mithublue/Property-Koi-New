<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyMedia;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyMedia::with('property')->latest();

        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('search', ''));
        $type   = $request->query('type', 'all');

        // 1. Text Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('caption', 'like', "%{$search}%")
                  ->orWhere('file_path', 'like', "%{$search}%")
                  ->orWhereHas('property', function ($pq) use ($search) {
                      $pq->where('title', 'like', "%{$search}%")
                         ->orWhere('property_code', 'like', "%{$search}%")
                         ->orWhere('block', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Attachment / Storage Filters
        if ($filter === 'assigned') {
            $query->whereNotNull('property_id');
        } elseif ($filter === 'unassigned') {
            $query->whereNull('property_id');
        } elseif ($filter === 'cloudinary') {
            $query->where('file_path', 'like', 'https://res.cloudinary.com/%');
        } elseif ($filter === 'local') {
            $query->where('file_path', 'not like', 'https://res.cloudinary.com/%')
                  ->where('file_path', 'not like', 'http%');
        }

        // 3. Media Type Filter
        if ($type !== 'all' && in_array($type, ['photo', 'video_embed', 'document'])) {
            $query->where('media_type', $type);
        }

        $mediaItems = $query->paginate(24)->withQueryString();

        $stats = [
            'total'      => PropertyMedia::count(),
            'cloudinary' => PropertyMedia::where('file_path', 'like', 'https://res.cloudinary.com/%')->count(),
            'assigned'   => PropertyMedia::whereNotNull('property_id')->count(),
            'unassigned' => PropertyMedia::whereNull('property_id')->count(),
            'local'      => PropertyMedia::where('file_path', 'not like', 'https://res.cloudinary.com/%')->where('file_path', 'not like', 'http%')->count(),
        ];

        $properties = Property::select('id', 'title', 'property_code')->latest()->get();

        $isCloudinaryReady = CloudinaryService::isConfigured();

        return view('admin.media', compact(
            'mediaItems',
            'stats',
            'properties',
            'filter',
            'search',
            'type',
            'isCloudinaryReady'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files'       => 'nullable|array',
            'files.*'     => 'image|max:15360', // 15MB
            'file'        => 'nullable|image|max:15360',
            'property_id' => 'nullable|exists:properties,id',
            'caption'     => 'nullable|string|max:255',
            'is_cover'    => 'nullable|boolean',
        ]);

        $files = $request->file('files') ?: ($request->file('file') ? [$request->file('file')] : []);
        if (empty($files)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'কোনো ফাইল নির্বাচন করা হয়নি।'], 422);
            }
            return back()->with('error', 'কোনো ফাইল নির্বাচন করা হয়নি।');
        }

        $propertyId = $request->input('property_id') ?: null;
        $isCover    = $request->boolean('is_cover', false);
        $caption    = $request->input('caption');

        $uploadedRecords = [];
        $errors = [];

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;

            // Upload to Cloudinary (auto-converts to WebP)
            $cloudUrl = CloudinaryService::upload($file);

            if ($cloudUrl) {
                // If set as cover, unset other covers for this property
                if ($isCover && $propertyId) {
                    PropertyMedia::where('property_id', $propertyId)->update(['is_cover' => false]);
                }

                $record = PropertyMedia::create([
                    'property_id' => $propertyId,
                    'file_path'   => $cloudUrl,
                    'media_type'  => 'photo',
                    'is_cover'    => $isCover,
                    'caption'     => $caption ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'sort_order'  => PropertyMedia::where('property_id', $propertyId)->count() + 1,
                ]);

                $uploadedRecords[] = $record;
            } else {
                $errors[] = $file->getClientOriginalName();
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => count($uploadedRecords) > 0,
                'count'   => count($uploadedRecords),
                'data'    => $uploadedRecords,
                'errors'  => $errors,
                'message' => count($uploadedRecords) . 'টি মিডিয়া ফাইল সফলভাবে WebP কনভার্ট হয়ে Cloudinary-তে আপলোড হয়েছে।',
            ]);
        }

        return back()->with('success', count($uploadedRecords) . 'টি ছবি সফলভাবে WebP কনভার্ট হয়ে Cloudinary-তে আপলোড হয়েছে!');
    }

    public function destroy(PropertyMedia $media)
    {
        $filePath = $media->file_path;

        // 1. Delete from Cloudinary if hosted there
        if (Str::startsWith($filePath, 'https://res.cloudinary.com/')) {
            CloudinaryService::delete($filePath);
        } elseif (!Str::startsWith($filePath, 'http')) {
            // Delete local file if exists
            $local = public_path($filePath);
            if (file_exists($local)) {
                @unlink($local);
            }
        }

        // 2. Delete database record
        $media->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'মিডিয়া ফাইলটি Cloudinary এবং ডাটাবেস থেকে স্থায়ীভাবে মুছে ফেলা হয়েছে।',
            ]);
        }

        return back()->with('success', 'মিডিয়া ফাইলটি Cloudinary ও ডাটাবেস থেকে স্থায়ীভাবে ডিলিট করা হয়েছে।');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'কোনো মিডিয়া নির্বাচন করা হয়নি।'], 422);
        }

        $items = PropertyMedia::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($items as $item) {
            $filePath = $item->file_path;

            if (Str::startsWith($filePath, 'https://res.cloudinary.com/')) {
                CloudinaryService::delete($filePath);
            } elseif (!Str::startsWith($filePath, 'http')) {
                $local = public_path($filePath);
                if (file_exists($local)) {
                    @unlink($local);
                }
            }

            $item->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'count'   => $deletedCount,
            'message' => "{$deletedCount}টি মিডিয়া ফাইল Cloudinary ও ডাটাবেস থেকে সফলভাবে ডিলিট করা হয়েছে।",
        ]);
    }

    public function update(Request $request, PropertyMedia $media)
    {
        $validated = $request->validate([
            'property_id' => 'nullable|exists:properties,id',
            'caption'     => 'nullable|string|max:255',
            'is_cover'    => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        $propId = $request->input('property_id') ?: null;
        $isCover = $request->boolean('is_cover');

        if ($isCover && $propId) {
            PropertyMedia::where('property_id', $propId)->where('id', '!=', $media->id)->update(['is_cover' => false]);
        }

        $media->update([
            'property_id' => $propId,
            'caption'     => $validated['caption'] ?? $media->caption,
            'is_cover'    => $isCover,
            'sort_order'  => (int) ($validated['sort_order'] ?? $media->sort_order),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'মিডিয়া তথ্য আপডেট করা হয়েছে।',
                'media'   => $media->fresh(['property']),
            ]);
        }

        return back()->with('success', 'মিডিয়া তথ্য সফলভাবে আপডেট করা হয়েছে।');
    }

    public function syncCloudinary(Request $request)
    {
        Artisan::call('media:migrate-cloudinary', ['--delete-local' => 1]);
        $output = Artisan::output();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'সকল লোকাল মিডিয়া সফলভাবে Cloudinary-তে মাইগ্রেট ও লোকাল কপি ডিলিট করা হয়েছে!',
                'log'     => $output,
            ]);
        }

        return back()->with('success', 'সকল লোকাল ফাইল সফলভাবে Cloudinary-তে আপলোড ও লোকাল ড্রাইভ থেকে ডিলিট করা হয়েছে!');
    }
}
