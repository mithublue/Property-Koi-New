<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\CloudinaryService;
use App\Services\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $query = Guide::query()->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $isPublished = $request->status === 'published';
            $query->where('is_published', $isPublished);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        $guides = $query->paginate(15)->withQueryString();
        $categories = Guide::getCategoryOptions();

        return view('admin.guides.index', compact('guides', 'categories'));
    }

    public function create(): View
    {
        $guide = new Guide([
            'is_published'     => true,
            'is_featured'      => false,
            'reading_time_min' => 5,
            'category'         => 'uncategorised',
            'author_name'      => 'PropertyKoi Research Team',
            'faqs'             => [],
        ]);

        $categories = Guide::getCategoryOptions();

        return view('admin.guides.form', compact('guide', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:guides,slug',
            'category'         => 'nullable|string|max:100',
            'custom_category'  => 'nullable|string|max:100',
            'excerpt'          => 'nullable|string|max:500',
            'content'          => 'required|string',
            'cover_image'      => 'nullable|string|max:500',
            'cover_image_file' => 'nullable|image|max:10240',
            'reading_time_min' => 'nullable|integer|min:1|max:60',
            'author_name'      => 'nullable|string|max:100',
            'seo_title'        => 'nullable|string|max:255',
            'seo_description'  => 'nullable|string|max:300',
            'seo_keywords'     => 'nullable|string|max:300',
            'is_featured'      => 'nullable|boolean',
            'is_published'     => 'nullable|boolean',
            'faq_questions'    => 'nullable|array',
            'faq_answers'      => 'nullable|array',
        ]);

        // Auto-generate or sanitize slug
        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        if (empty($slug)) {
            $slug = 'guide-' . time();
        }

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Guide::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $counter++;
        }
        $validated['slug'] = $slug;

        // Custom Category or default uncategorised
        if (!empty($request->custom_category)) {
            $validated['category'] = trim($request->custom_category);
        } else {
            $validated['category'] = !empty($validated['category']) ? $validated['category'] : 'uncategorised';
        }

        // Handle direct file upload for featured image -> WebP -> Cloudinary
        if ($request->hasFile('cover_image_file')) {
            $uploadedUrl = self::processCoverImage($request->file('cover_image_file'));
            if ($uploadedUrl) {
                $validated['cover_image'] = $uploadedUrl;
            }
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['reading_time_min'] = $validated['reading_time_min'] ?? max(1, (int) ceil(str_word_count(strip_tags($validated['content'])) / 180));

        // Format FAQs
        $faqs = [];
        if (!empty($request->faq_questions) && is_array($request->faq_questions)) {
            foreach ($request->faq_questions as $index => $question) {
                $ans = $request->faq_answers[$index] ?? '';
                if (!empty(trim($question)) && !empty(trim($ans))) {
                    $faqs[] = [
                        'question' => trim($question),
                        'answer'   => trim($ans),
                    ];
                }
            }
        }
        $validated['faqs'] = $faqs;

        $guide = Guide::create($validated);

        return redirect()->route('admin.guides.index')->with('success', 'গাইড সফলভাবে তৈরি ও সংরক্ষণ করা হয়েছে!');
    }

    public function edit(Guide $guide): View
    {
        $categories = Guide::getCategoryOptions();
        return view('admin.guides.form', compact('guide', 'categories'));
    }

    public function update(Request $request, Guide $guide): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:guides,slug,' . $guide->id,
            'category'         => 'nullable|string|max:100',
            'custom_category'  => 'nullable|string|max:100',
            'excerpt'          => 'nullable|string|max:500',
            'content'          => 'required|string',
            'cover_image'      => 'nullable|string|max:500',
            'cover_image_file' => 'nullable|image|max:10240',
            'reading_time_min' => 'nullable|integer|min:1|max:60',
            'author_name'      => 'nullable|string|max:100',
            'seo_title'        => 'nullable|string|max:255',
            'seo_description'  => 'nullable|string|max:300',
            'seo_keywords'     => 'nullable|string|max:300',
            'is_featured'      => 'nullable|boolean',
            'is_published'     => 'nullable|boolean',
            'faq_questions'    => 'nullable|array',
            'faq_answers'      => 'nullable|array',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        // Custom Category or selected category
        if (!empty($request->custom_category)) {
            $validated['category'] = trim($request->custom_category);
        } else {
            $validated['category'] = !empty($validated['category']) ? $validated['category'] : 'uncategorised';
        }

        // Handle direct file upload for featured image -> WebP -> Cloudinary
        if ($request->hasFile('cover_image_file')) {
            $uploadedUrl = self::processCoverImage($request->file('cover_image_file'));
            if ($uploadedUrl) {
                $validated['cover_image'] = $uploadedUrl;
            }
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['reading_time_min'] = $validated['reading_time_min'] ?? max(1, (int) ceil(str_word_count(strip_tags($validated['content'])) / 180));

        // Format FAQs
        $faqs = [];
        if (!empty($request->faq_questions) && is_array($request->faq_questions)) {
            foreach ($request->faq_questions as $index => $question) {
                $ans = $request->faq_answers[$index] ?? '';
                if (!empty(trim($question)) && !empty(trim($ans))) {
                    $faqs[] = [
                        'question' => trim($question),
                        'answer'   => trim($ans),
                    ];
                }
            }
        }
        $validated['faqs'] = $faqs;

        $guide->update($validated);

        return redirect()->route('admin.guides.index')->with('success', 'গাইড তথ্য সফলভাবে আপডেট করা হয়েছে!');
    }

    public function destroy(Guide $guide): RedirectResponse
    {
        $guide->delete();
        return redirect()->route('admin.guides.index')->with('success', 'গাইড মুছে ফেলা হয়েছে!');
    }

    public function togglePublished(Guide $guide): RedirectResponse
    {
        $guide->is_published = !$guide->is_published;
        $guide->save();

        $statusText = $guide->is_published ? 'প্রকাশিত' : 'ড্রাফট';
        return back()->with('success', "গাইডটির স্ট্যাটাস {$statusText} করা হয়েছে।");
    }

    /**
     * Upload Image handler for WYSIWYG editor (drag/drop or image button).
     * Automatically converts to WebP and saves to Cloudinary.
     */
    public function uploadEditorImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:10240',
        ]);

        $file = $request->file('file');
        $url = self::processCoverImage($file);

        if ($url) {
            return response()->json([
                'location' => $url,
            ]);
        }

        return response()->json(['error' => 'ছবি আপলোড ও WebP কনভার্ট করা সম্ভব হয়নি'], 422);
    }

    /**
     * AJAX Endpoint to upload Featured Thumbnail directly.
     */
    public function uploadThumbnail(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $file = $request->file('image');
        $url = self::processCoverImage($file);

        if ($url) {
            return response()->json([
                'success' => true,
                'url'     => $url,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'থাম্বনেইল আপলোড বা WebP তে রূপান্তর সম্ভব হয়নি।',
        ], 422);
    }

    /**
     * Process an image file: convert to WebP and save to Cloudinary (fallback to local).
     */
    protected static function processCoverImage($file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $uploadDir = public_path('uploads/guides');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'guide_' . date('Ymd_His') . '_' . Str::random(8) . '.webp';
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $relativeUrl = 'uploads/guides/' . $filename;

        $converted = WatermarkService::convertToWebp($file->getRealPath(), $destPath, false);
        if ($converted && file_exists($destPath)) {
            // Upload to Cloudinary with WebP format
            $cloudUrl = CloudinaryService::upload($destPath, 'propertykoi_guides');
            if ($cloudUrl) {
                @unlink($destPath);
                return $cloudUrl;
            }
            return asset($relativeUrl);
        }

        return null;
    }

    /**
     * One-Click Database Seeder Trigger from Admin UI.
     * Runs GuideSeeder or DatabaseSeeder via web request on PHP 8.3 without needing CLI terminal access.
     */
    public function seed(Request $request): RedirectResponse
    {
        $target = $request->input('target', 'guides');

        try {
            $class = match ($target) {
                'all', 'database' => 'Database\\Seeders\\DatabaseSeeder',
                default           => 'Database\\Seeders\\GuideSeeder',
            };

            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => $class,
                '--force' => true,
            ]);

            \Illuminate\Support\Facades\Artisan::call('optimize:clear');

            $label = $target === 'all'
                ? 'সম্পূর্ণ ডেটাবেজ ও নলেজ হাব'
                : '১০টি এআই ও এসইও অপ্টিমাইজড নলেজ হাব গাইড';

            return redirect()->route('admin.guides.index')
                ->with('success', "অভিনন্দন! ১-ক্লিকে {$label} সফলভাবে ডেটাবেজে সিড ও হালনাগাদ সম্পন্ন হয়েছে।");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Seeder execution failed from Admin UI: ' . $e->getMessage());

            return redirect()->route('admin.guides.index')
                ->with('error', 'সিড সম্পন্ন করা সম্ভব হয়নি: ' . $e->getMessage());
        }
    }
}
