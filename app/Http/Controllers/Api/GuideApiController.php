<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GuideApiController extends Controller
{
    /**
     * List all property guides / articles with search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Guide::query();

        if ($request->boolean('published_only', true)) {
            $query->where('is_published', true);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $keyword = trim((string) $request->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('excerpt', 'like', "%{$keyword}%")
                  ->orWhere('slug', 'like', "%{$keyword}%")
                  ->orWhere('seo_keywords', 'like', "%{$keyword}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $guides = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $guides->map(function (Guide $g) {
                return [
                    'id'               => $g->id,
                    'title'            => $g->title,
                    'slug'             => $g->slug,
                    'category'         => $g->category,
                    'category_label'   => $g->category_label,
                    'excerpt'          => $g->excerpt,
                    'reading_time_min' => $g->reading_time_min,
                    'is_featured'      => (bool) $g->is_featured,
                    'is_published'     => (bool) $g->is_published,
                    'views_count'      => (int) $g->views_count,
                    'author_name'      => $g->author_name,
                    'cover_url'        => $g->cover_url,
                    'public_url'       => route('guides.show', $g->slug),
                    'created_at'       => $g->created_at?->toIso8601String(),
                    'updated_at'       => $g->updated_at?->toIso8601String(),
                ];
            }),
            'pagination' => [
                'total'        => $guides->total(),
                'current_page' => $guides->currentPage(),
                'last_page'    => $guides->lastPage(),
                'per_page'     => $guides->perPage(),
            ],
            'categories' => Guide::getCategoryOptions(),
        ]);
    }

    /**
     * Get a fast overview of all existing guide topics and slugs to prevent duplicates.
     */
    public function topics(): JsonResponse
    {
        $topics = Guide::select(['id', 'title', 'slug', 'category', 'is_published', 'created_at'])
            ->latest('id')
            ->get()
            ->map(function (Guide $g) {
                return [
                    'id'             => $g->id,
                    'title'          => $g->title,
                    'slug'           => $g->slug,
                    'category'       => $g->category,
                    'category_label' => $g->category_label,
                    'url'            => route('guides.show', $g->slug),
                ];
            });

        return response()->json([
            'success'      => true,
            'total_topics' => $topics->count(),
            'topics'       => $topics,
        ]);
    }

    /**
     * Check if a proposed topic/title is unique before publishing.
     */
    public function checkUniqueness(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|min:5',
            'slug'  => 'nullable|string',
        ]);

        $proposedTitle = trim((string) $request->title);
        $proposedSlug  = $request->filled('slug')
            ? Str::slug($request->slug)
            : Str::slug($proposedTitle);

        // 1. Exact slug match
        $exactSlug = Guide::where('slug', $proposedSlug)->first();
        if ($exactSlug) {
            return response()->json([
                'success'           => true,
                'is_unique'         => false,
                'similarity_score'  => 100,
                'conflict_reason'   => 'A guide with the exact same slug already exists.',
                'conflicting_guide' => [
                    'id'    => $exactSlug->id,
                    'title' => $exactSlug->title,
                    'slug'  => $exactSlug->slug,
                ],
            ]);
        }

        // 2. Title similarity scan against all guides
        $existing = Guide::select(['id', 'title', 'slug'])->get();
        $highestSimilarity = 0;
        $mostSimilarGuide  = null;

        foreach ($existing as $guide) {
            similar_text(mb_strtolower($proposedTitle), mb_strtolower($guide->title), $percent);
            if ($percent > $highestSimilarity) {
                $highestSimilarity = round($percent, 2);
                $mostSimilarGuide  = $guide;
            }
        }

        $isUnique = $highestSimilarity < 75.0;

        return response()->json([
            'success'           => true,
            'is_unique'         => $isUnique,
            'similarity_score'  => $highestSimilarity,
            'message'           => $isUnique
                ? 'Topic is unique and ready for aggressive SEO/AEO publication.'
                : 'Warning: Topic is highly similar to an existing guide. Consider refining the angle or title.',
            'conflicting_guide' => $mostSimilarGuide ? [
                'id'         => $mostSimilarGuide->id,
                'title'      => $mostSimilarGuide->title,
                'slug'       => $mostSimilarGuide->slug,
                'similarity' => $highestSimilarity . '%',
            ] : null,
        ]);
    }

    /**
     * Show detailed guide data with SEO & AEO metadata.
     */
    public function show(string $identifier): JsonResponse
    {
        $guide = is_numeric($identifier)
            ? Guide::find($identifier)
            : Guide::where('slug', $identifier)->first();

        if (!$guide) {
            return response()->json([
                'success' => false,
                'message' => "Guide not found for identifier '{$identifier}'.",
            ], 404);
        }

        $ogMeta = SeoService::getGuideMeta($guide);

        return response()->json([
            'success' => true,
            'data'    => array_merge($guide->toArray(), [
                'category_label' => $guide->category_label,
                'cover_url'      => $guide->cover_url,
                'public_url'     => route('guides.show', $guide->slug),
                'seo_meta'       => $ogMeta,
            ]),
        ]);
    }

    /**
     * Create and publish a new property guide via API.
     */
    public function store(Request $request): JsonResponse
    {
        $slugRule = 'nullable|string|max:255';
        if (!$request->boolean('update_existing')) {
            $slugRule .= '|unique:guides,slug';
        }

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => $slugRule,
            'category'         => 'nullable|string|max:100',
            'excerpt'          => 'nullable|string|max:1000',
            'content'          => 'required|string',
            'reading_time_min' => 'nullable|integer|min:1|max:120',
            'cover_image'      => 'nullable|string|max:500',
            'author_name'      => 'nullable|string|max:100',
            'seo_title'        => 'nullable|string|max:255',
            'seo_description'  => 'nullable|string|max:500',
            'seo_keywords'     => 'nullable|string',
            'is_featured'      => 'nullable|boolean',
            'is_published'     => 'nullable|boolean',
            'faqs'             => 'nullable|array',
            'en_profile'       => 'nullable|array',
            'force'            => 'nullable|boolean',
            'update_existing'  => 'nullable|boolean',
        ]);

        $title = trim($validated['title']);

        // Auto-generate slug if not provided
        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($title);

        if (empty($slug)) {
            $slug = 'guide-' . date('Ymd-His');
        }

        // Auto-calculate reading time if not explicitly provided
        $readingTime = (int) ($validated['reading_time_min'] ?? 0);
        if ($readingTime <= 0) {
            $wordCount = str_word_count(strip_tags($validated['content']));
            $readingTime = max(2, (int) ceil($wordCount / 160));
        }

        // Handle category fallback
        $category = !empty($validated['category']) ? trim($validated['category']) : 'buying_guides';

        // Author name
        $author = !empty($validated['author_name']) ? trim($validated['author_name']) : 'Property Koi Desk';

        // Fallback excerpt
        $excerpt = !empty($validated['excerpt'])
            ? trim($validated['excerpt'])
            : Str::limit(strip_tags($validated['content']), 220);

        // If updating existing record by slug
        if ($request->boolean('update_existing')) {
            $existingGuide = Guide::where('slug', $slug)->first();
            if ($existingGuide) {
                $existingGuide->update([
                    'title'            => $title,
                    'category'         => $category,
                    'excerpt'          => $excerpt,
                    'content'          => $validated['content'],
                    'reading_time_min' => $readingTime,
                    'cover_image'      => $validated['cover_image'] ?? $existingGuide->cover_image,
                    'author_name'      => $author,
                    'seo_title'        => $validated['seo_title'] ?? $title,
                    'seo_description'  => $validated['seo_description'] ?? $excerpt,
                    'seo_keywords'     => $validated['seo_keywords'] ?? null,
                    'is_featured'      => $request->boolean('is_featured', $existingGuide->is_featured),
                    'is_published'     => $request->has('is_published') ? $request->boolean('is_published') : $existingGuide->is_published,
                    'faqs'             => $validated['faqs'] ?? $existingGuide->faqs,
                    'en_profile'       => $validated['en_profile'] ?? $existingGuide->en_profile,
                ]);

                $this->saveLocalBackup($existingGuide);

                return response()->json([
                    'success' => true,
                    'action'  => 'updated',
                    'message' => "Guide '{$existingGuide->title}' updated on live successfully.",
                    'data'    => array_merge($existingGuide->fresh()->toArray(), [
                        'public_url' => route('guides.show', $existingGuide->slug),
                    ]),
                ], 200);
            }
        }

        // Check topic uniqueness unless force flag is passed
        if (!$request->boolean('force')) {
            $existing = Guide::select(['id', 'title', 'slug'])->get();
            foreach ($existing as $g) {
                similar_text(mb_strtolower($title), mb_strtolower($g->title), $percent);
                if ($percent >= 85.0) {
                    return response()->json([
                        'success'           => false,
                        'message'           => 'A guide on a virtually identical topic already exists. To force publication, pass "force": true.',
                        'conflicting_guide' => [
                            'id'         => $g->id,
                            'title'      => $g->title,
                            'slug'       => $g->slug,
                            'similarity' => round($percent, 2) . '%',
                        ],
                    ], 422);
                }
            }
        }

        // Prepare Guide record
        $guide = Guide::create([
            'title'            => $title,
            'slug'             => $slug,
            'category'         => $category,
            'excerpt'          => $excerpt,
            'content'          => $validated['content'],
            'cover_image'      => $validated['cover_image'] ?? null,
            'reading_time_min' => $readingTime,
            'faqs'             => $validated['faqs'] ?? [],
            'en_profile'       => $validated['en_profile'] ?? null,
            'seo_title'        => $validated['seo_title'] ?? null,
            'seo_description'  => $validated['seo_description'] ?? null,
            'seo_keywords'     => $validated['seo_keywords'] ?? null,
            'is_featured'      => $request->boolean('is_featured', false),
            'is_published'     => $request->boolean('is_published', true),
            'author_name'      => $author,
        ]);

        // Save local JSON backup into content/guides/ for version control and clean local backup
        $this->saveLocalBackup($guide);

        // Ping search engine indexer (IndexNow)
        try {
            SeoService::submitToIndexNow();
        } catch (\Throwable $e) {
            // Non-fatal
        }

        return response()->json([
            'success'    => true,
            'message'    => "Property guide '{$guide->title}' published successfully via API.",
            'data'       => array_merge($guide->toArray(), [
                'category_label' => $guide->category_label,
                'cover_url'      => $guide->cover_url,
                'public_url'     => route('guides.show', $guide->slug),
            ]),
        ], 201);
    }

    /**
     * Update an existing guide via API.
     */
    public function update(Request $request, string $identifier): JsonResponse
    {
        $guide = is_numeric($identifier)
            ? Guide::find($identifier)
            : Guide::where('slug', $identifier)->first();

        if (!$guide) {
            return response()->json([
                'success' => false,
                'message' => "Guide not found for identifier '{$identifier}'.",
            ], 404);
        }

        $validated = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'slug'             => 'sometimes|nullable|string|max:255|unique:guides,slug,' . $guide->id,
            'category'         => 'sometimes|nullable|string|max:100',
            'excerpt'          => 'sometimes|nullable|string|max:1000',
            'content'          => 'sometimes|required|string',
            'reading_time_min' => 'sometimes|nullable|integer|min:1|max:120',
            'cover_image'      => 'sometimes|nullable|string|max:500',
            'author_name'      => 'sometimes|nullable|string|max:100',
            'seo_title'        => 'sometimes|nullable|string|max:255',
            'seo_description'  => 'sometimes|nullable|string|max:500',
            'seo_keywords'     => 'sometimes|nullable|string',
            'is_featured'      => 'sometimes|nullable|boolean',
            'is_published'     => 'sometimes|nullable|boolean',
            'faqs'             => 'sometimes|nullable|array',
            'en_profile'       => 'sometimes|nullable|array',
        ]);

        $guide->update($validated);
        $this->saveLocalBackup($guide);

        return response()->json([
            'success' => true,
            'message' => "Guide '{$guide->title}' updated successfully.",
            'data'    => array_merge($guide->fresh()->toArray(), [
                'public_url' => route('guides.show', $guide->slug),
            ]),
        ]);
    }

    /**
     * Delete a guide via API.
     */
    public function destroy(string $identifier): JsonResponse
    {
        $guide = is_numeric($identifier)
            ? Guide::find($identifier)
            : Guide::where('slug', $identifier)->first();

        if (!$guide) {
            return response()->json([
                'success' => false,
                'message' => "Guide not found for identifier '{$identifier}'.",
            ], 404);
        }

        $title = $guide->title;
        $guide->delete();

        return response()->json([
            'success' => true,
            'message' => "Guide '{$title}' was successfully deleted.",
        ]);
    }

    /**
     * Save a clean local JSON backup of the guide in content/guides/{slug}.json.
     * Prevents database bloat while maintaining git-trackable repository archives.
     */
    protected function saveLocalBackup(Guide $guide): void
    {
        if (app()->environment('testing')) {
            return;
        }

        try {
            $dir = base_path('content/guides');
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $filePath = $dir . DIRECTORY_SEPARATOR . $guide->slug . '.json';
            $data = [
                'id'               => $guide->id,
                'title'            => $guide->title,
                'slug'             => $guide->slug,
                'category'         => $guide->category,
                'reading_time_min' => $guide->reading_time_min,
                'author_name'      => $guide->author_name,
                'excerpt'          => $guide->excerpt,
                'cover_image'      => $guide->cover_image,
                'seo_title'        => $guide->seo_title,
                'seo_description'  => $guide->seo_description,
                'seo_keywords'     => $guide->seo_keywords,
                'faqs'             => $guide->faqs,
                'en_profile'       => $guide->en_profile,
                'is_featured'      => (bool) $guide->is_featured,
                'is_published'     => (bool) $guide->is_published,
                'updated_at'       => $guide->updated_at?->toIso8601String(),
                'content'          => $guide->content,
            ];

            File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            // Silently continue if filesystem write has local permissions constraint
        }
    }
}
