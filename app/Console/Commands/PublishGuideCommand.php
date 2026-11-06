<?php

namespace App\Console\Commands;

use App\Models\Guide;
use App\Services\SeoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishGuideCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guide:publish 
                            {--file= : Path to JSON guide file}
                            {--force : Force publishing even if topic similarity is detected}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a new property guide/article with high SEO & AEO optimization from a JSON file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file');

        if (empty($filePath)) {
            $this->error('Please specify a guide JSON file via --file=<path>.');
            $this->info('Example: php artisan guide:publish --file=content/guides/my-new-guide.json');
            return 1;
        }

        $fullPath = File::exists($filePath) ? $filePath : base_path($filePath);

        if (!File::exists($fullPath)) {
            $this->error("File not found at: {$fullPath}");
            return 1;
        }

        $rawJson = File::get($fullPath);
        $data = json_decode($rawJson, true);

        if (!is_array($data) || empty($data['title']) || empty($data['content'])) {
            $this->error('Invalid guide JSON. "title" and "content" are required fields.');
            return 1;
        }

        $title = trim($data['title']);
        $slug  = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($title);

        // Check topic uniqueness
        if (!$this->option('force')) {
            $existing = Guide::select(['id', 'title', 'slug'])->get();
            foreach ($existing as $g) {
                if ($g->slug === $slug) {
                    $this->error("Slug '{$slug}' already exists in guide ID #{$g->id} ('{$g->title}'). Use --force to override.");
                    return 1;
                }

                similar_text(mb_strtolower($title), mb_strtolower($g->title), $percent);
                if ($percent >= 85.0) {
                    $this->warn("High similarity ({$percent}%) with existing guide ID #{$g->id} ('{$g->title}'). Use --force to proceed.");
                    return 1;
                }
            }
        }

        // Reading time
        $readingTime = (int) ($data['reading_time_min'] ?? 0);
        if ($readingTime <= 0) {
            $wordCount = str_word_count(strip_tags($data['content']));
            $readingTime = max(2, (int) ceil($wordCount / 160));
        }

        $guide = Guide::updateOrCreate(
            ['slug' => $slug],
            [
                'title'            => $title,
                'category'         => $data['category'] ?? 'buying_guides',
                'excerpt'          => $data['excerpt'] ?? null,
                'content'          => $data['content'],
                'reading_time_min' => $readingTime,
                'author_name'      => $data['author_name'] ?? 'Property Koi Desk',
                'cover_image'      => $data['cover_image'] ?? null,
                'seo_title'        => $data['seo_title'] ?? null,
                'seo_description'  => $data['seo_description'] ?? null,
                'seo_keywords'     => $data['seo_keywords'] ?? null,
                'faqs'             => $data['faqs'] ?? [],
                'en_profile'       => $data['en_profile'] ?? null,
                'is_featured'      => (bool) ($data['is_featured'] ?? false),
                'is_published'     => (bool) ($data['is_published'] ?? true),
            ]
        );

        // Notify IndexNow
        try {
            SeoService::submitToIndexNow();
        } catch (\Throwable $e) {}

        $this->info("✅ Successfully published guide: '{$guide->title}'");
        $this->info("🔗 Public URL: " . route('guides.show', $guide->slug));
        $this->info("📑 Category: {$guide->category_label}");
        $this->info("⏱️ Reading Time: {$guide->reading_time_min} mins");

        return 0;
    }
}
