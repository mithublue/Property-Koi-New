<?php

namespace App\Services;

use App\Models\Guide;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuidePublisherService
{
    public const DEFAULT_LIVE_URL = 'https://propertykoi.com';

    public const DEFAULT_API_KEY = 'propertykoi_guides_sec_2026';

    public const DEFAULT_SCOPE = 'latest_unpublished'; // 'latest_unpublished', 'all', 'specific'

    public const DEFAULT_STATUS = 'published'; // 'published', 'draft'

    /**
     * Get all current guide publishing configurations.
     */
    public static function getSettings(): array
    {
        $specificIdsRaw = Setting::get('adm_guide_publish_specific_ids', '[]');
        $specificIds = is_array($specificIdsRaw)
            ? $specificIdsRaw
            : (json_decode((string) $specificIdsRaw, true) ?: []);

        return [
            'live_site_url' => rtrim(trim((string) Setting::get('adm_guide_live_site_url', self::DEFAULT_LIVE_URL)), '/'),
            'api_key' => trim((string) Setting::get('adm_guide_api_key', Setting::get('adm_api_secret_key', self::DEFAULT_API_KEY))),
            'publish_scope' => Setting::get('adm_guide_publish_scope', self::DEFAULT_SCOPE),
            'specific_ids' => array_values(array_filter(array_map('intval', (array) $specificIds))),
            'default_status' => Setting::get('adm_guide_publish_default_status', self::DEFAULT_STATUS),
            'last_publish_time' => Setting::get('adm_guide_last_publish_time', null),
            'last_publish_report' => json_decode((string) Setting::get('adm_guide_last_publish_report', '[]'), true) ?: null,
        ];
    }

    /**
     * Save publisher settings.
     */
    public static function saveSettings(array $data): void
    {
        if (isset($data['live_site_url'])) {
            Setting::set('adm_guide_live_site_url', rtrim(trim($data['live_site_url']), '/'));
        }

        if (isset($data['api_key'])) {
            Setting::set('adm_guide_api_key', trim($data['api_key']));
        }

        if (isset($data['publish_scope'])) {
            $scope = in_array($data['publish_scope'], ['latest_unpublished', 'all', 'specific'], true)
                ? $data['publish_scope']
                : self::DEFAULT_SCOPE;
            Setting::set('adm_guide_publish_scope', $scope);
        }

        if (isset($data['specific_ids'])) {
            $ids = is_array($data['specific_ids'])
                ? array_values(array_filter(array_map('intval', $data['specific_ids'])))
                : [];
            Setting::set('adm_guide_publish_specific_ids', $ids);
        }

        if (isset($data['default_status'])) {
            $status = in_array($data['default_status'], ['published', 'draft'], true)
                ? $data['default_status']
                : self::DEFAULT_STATUS;
            Setting::set('adm_guide_publish_default_status', $status);
        }
    }

    /**
     * Resolve the collection of guides to publish based on scope or parameters.
     */
    public static function getTargetGuides(?string $scope = null, ?array $specificIds = null): Collection
    {
        $settings = self::getSettings();
        $scope = $scope ?: $settings['publish_scope'];

        if ($scope === 'specific') {
            $ids = ! empty($specificIds) ? $specificIds : $settings['specific_ids'];
            if (empty($ids)) {
                return new Collection;
            }

            return Guide::whereIn('id', $ids)->latest('id')->get();
        }

        if ($scope === 'all') {
            return Guide::latest('id')->get();
        }

        // Default: 'latest_unpublished' (not yet synced to live or drafts)
        $guides = Guide::where(function ($q) {
            $q->whereNull('synced_to_live_at')
                ->orWhere('is_published', false);
        })->latest('id')->get();

        // If none found with null sync date, check if any guides were created after last publish time
        if ($guides->isEmpty()) {
            $lastPublishTime = Setting::get('adm_guide_last_publish_time');
            if ($lastPublishTime) {
                $guides = Guide::where('created_at', '>', $lastPublishTime)->latest('id')->get();
            }
        }

        // If still empty, return latest 3 guides as default fallback
        if ($guides->isEmpty()) {
            $guides = Guide::latest('id')->take(3)->get();
        }

        return $guides;
    }

    /**
     * Publish selected guides to the live site.
     *
     * @param  array  $options  [
     *                          'scope' => 'latest_unpublished'|'all'|'specific',
     *                          'specific_ids' => [1, 2],
     *                          'default_status' => 'published'|'draft',
     *                          'live_site_url' => 'https://...',
     *                          'api_key' => '...'
     *                          ]
     */
    public static function publish(array $options = []): array
    {
        $settings = self::getSettings();

        $scope = $options['scope'] ?? $settings['publish_scope'];
        $specificIds = $options['specific_ids'] ?? $settings['specific_ids'];
        $targetUrl = rtrim($options['live_site_url'] ?? $settings['live_site_url'], '/');
        $apiKey = $options['api_key'] ?? $settings['api_key'];
        $defaultStatus = $options['default_status'] ?? $settings['default_status'];
        $targetPublished = ($defaultStatus === 'published');

        $guides = self::getTargetGuides($scope, $specificIds);

        if ($guides->isEmpty()) {
            return [
                'success' => false,
                'total' => 0,
                'published' => [],
                'failed' => [],
                'target_url' => $targetUrl,
                'scope' => $scope,
                'message' => 'পাবলিশ করার মতো কোনো ব্লগ পাওয়া যায়নি। সেটিংস থেকে "সকল ব্লগ" অথবা "নির্দিষ্ট ব্লগ" নির্বাচন করুন।',
            ];
        }

        // Determine if target URL is local or remote
        $isLocalTarget = self::isLocalTarget($targetUrl);

        $successful = [];
        $failed = [];

        foreach ($guides as $guide) {
            $payload = [
                'title' => $guide->title,
                'slug' => $guide->slug,
                'category' => $guide->category,
                'excerpt' => $guide->excerpt,
                'content' => $guide->content,
                'reading_time_min' => $guide->reading_time_min,
                'cover_image' => $guide->cover_url,
                'author_name' => $guide->author_name,
                'seo_title' => $guide->seo_title,
                'seo_description' => $guide->seo_description,
                'seo_keywords' => $guide->seo_keywords,
                'is_featured' => (bool) $guide->is_featured,
                'is_published' => $targetPublished,
                'faqs' => $guide->faqs ?? [],
                'en_profile' => $guide->en_profile ?? [],
                'force' => true,
                'update_existing' => true,
            ];

            if ($isLocalTarget) {
                // Direct database update for local/same-site target
                $guide->is_published = $targetPublished;
                $guide->synced_to_live_at = now();
                $guide->save();

                $successful[] = [
                    'id' => $guide->id,
                    'title' => $guide->title,
                    'slug' => $guide->slug,
                    'status' => $targetPublished ? 'published' : 'draft',
                    'action' => 'local_updated',
                ];
            } else {
                // Remote API call
                try {
                    $response = Http::timeout(30)
                        ->withoutVerifying()
                        ->withHeaders([
                            'X-API-KEY' => $apiKey,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])
                        ->post("{$targetUrl}/api/v1/guides", $payload);

                    if ($response->successful()) {
                        // Mark local guide as published and synced to live
                        $guide->synced_to_live_at = now();
                        if ($targetPublished && ! $guide->is_published) {
                            $guide->is_published = true;
                        }
                        $guide->save();

                        $successful[] = [
                            'id' => $guide->id,
                            'title' => $guide->title,
                            'slug' => $guide->slug,
                            'status' => $targetPublished ? 'published' : 'draft',
                            'action' => $response->json('action') ?? 'synced',
                        ];
                    } else {
                        $errorMsg = $response->json('message') ?: ('HTTP '.$response->status().': '.$response->body());
                        $failed[] = [
                            'id' => $guide->id,
                            'title' => $guide->title,
                            'error' => $errorMsg,
                        ];
                        Log::error("Failed to publish guide [{$guide->id}] to {$targetUrl}: {$errorMsg}");
                    }
                } catch (\Throwable $e) {
                    $failed[] = [
                        'id' => $guide->id,
                        'title' => $guide->title,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Exception publishing guide [{$guide->id}] to {$targetUrl}: ".$e->getMessage());
                }
            }
        }

        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
        } catch (\Throwable $t) {
            // ignore cache clear errors
        }

        $report = [
            'success' => count($successful) > 0,
            'total' => $guides->count(),
            'published' => $successful,
            'failed' => $failed,
            'target_url' => $targetUrl,
            'scope' => $scope,
            'timestamp' => now()->toDateTimeString(),
        ];

        Setting::set('adm_guide_last_publish_time', now()->toDateTimeString());
        Setting::set('adm_guide_last_publish_report', $report);

        $msgParts = [];
        if (count($successful) > 0) {
            $msgParts[] = count($successful)."টি ব্লগ সফলভাবে {$targetUrl} এ পাবলিশ করা হয়েছে!";
        }
        if (count($failed) > 0) {
            $msgParts[] = count($failed).'টি ব্লগ পাবলিশ হতে ব্যর্থ হয়েছে (API বা নেটওয়ার্ক এরর)।';
        }

        $report['message'] = implode(' ', $msgParts) ?: 'কোনো পরিবর্তন হয়নি।';

        return $report;
    }

    /**
     * Publish a single guide directly.
     */
    public static function publishSingle(Guide $guide, array $options = []): array
    {
        return self::publish(array_merge($options, [
            'scope' => 'specific',
            'specific_ids' => [$guide->id],
        ]));
    }

    /**
     * Check whether target URL is equivalent to the current host/environment.
     */
    protected static function isLocalTarget(string $url): bool
    {
        $targetHost = parse_url($url, PHP_URL_HOST);
        $currentHost = request()->getHost();

        if (empty($targetHost)) {
            return true;
        }

        return strtolower($targetHost) === strtolower($currentHost);
    }
}
