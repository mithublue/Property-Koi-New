<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\CloudinaryService;
use App\Services\GuidePublisherService;
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

        $publishSettings = GuidePublisherService::getSettings();
        $targetGuides = GuidePublisherService::getTargetGuides();
        $targetGuidesCount = $targetGuides->count();
        $allGuidesList = Guide::select(['id', 'title', 'slug', 'is_published', 'category', 'created_at'])->latest('id')->get();

        return view('admin.guides.index', compact(
            'guides',
            'categories',
            'publishSettings',
            'targetGuides',
            'targetGuidesCount',
            'allGuidesList'
        ));
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

    /**
     * Trigger bulk publishing to the live site based on admin settings or request payload.
     */
    public function publishLive(Request $request): JsonResponse|RedirectResponse
    {
        $options = [];

        if ($request->filled('scope')) {
            $options['scope'] = $request->input('scope');
        }

        if ($request->has('specific_ids')) {
            $options['specific_ids'] = (array) $request->input('specific_ids');
        }

        if ($request->filled('default_status')) {
            $options['default_status'] = $request->input('default_status');
        }

        if ($request->filled('live_site_url')) {
            $options['live_site_url'] = $request->input('live_site_url');
        }

        if ($request->filled('api_key')) {
            $options['api_key'] = $request->input('api_key');
        }

        $report = GuidePublisherService::publish($options);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($report, $report['success'] ? 200 : 422);
        }

        if ($report['success']) {
            return redirect()->route('admin.guides.index')->with('success', $report['message']);
        }

        return redirect()->route('admin.guides.index')->with('error', $report['message']);
    }

    /**
     * Publish a single guide directly to the live site.
     */
    public function publishSingleLive(Request $request, Guide $guide): RedirectResponse
    {
        $report = GuidePublisherService::publishSingle($guide);

        if ($report['success']) {
            return back()->with('success', "গাইড '{$guide->title}' সফলভাবে লাইভ সাইটে পাবলিশ করা হয়েছে!");
        }

        $errorMsg = !empty($report['failed'][0]['error']) ? $report['failed'][0]['error'] : ($report['message'] ?? 'পাবলিশ ব্যর্থ হয়েছে');
        return back()->with('error', "লাইভ সাইটে পাবলিশ করা সম্ভব হয়নি: {$errorMsg}");
    }

    /**
     * Update and save publishing configuration settings.
     */
    public function savePublishSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'live_site_url'  => 'required|url|max:255',
            'api_key'        => 'nullable|string|max:255',
            'publish_scope'  => 'required|in:latest_unpublished,all,specific',
            'specific_ids'   => 'nullable|array',
            'default_status' => 'required|in:published,draft',
        ]);

        GuidePublisherService::saveSettings([
            'live_site_url'  => $validated['live_site_url'],
            'api_key'        => $validated['api_key'] ?? '',
            'publish_scope'  => $validated['publish_scope'],
            'specific_ids'   => $validated['specific_ids'] ?? [],
            'default_status' => $validated['default_status'],
        ]);

        return redirect()->route('admin.guides.index')
            ->with('success', 'লাইভ সাইট পাবলিশ সেটিংস সফলভাবে সংরক্ষণ করা হয়েছে!');
    }
}
