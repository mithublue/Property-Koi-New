<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudinaryService
{
    /**
     * Check if Cloudinary credentials are fully configured.
     */
    public static function isConfigured(): bool
    {
        $cloudName = Setting::get('adm_cloudinary_cloud_name');
        $apiKey    = Setting::get('adm_cloudinary_api_key');
        $apiSecret = Setting::get('adm_cloudinary_api_secret');

        return !empty($cloudName) && !empty($apiKey) && !empty($apiSecret);
    }

    /**
     * Automatically convert an image to WebP if not already WebP to save bandwidth & storage.
     * Returns the path to the converted WebP file, and whether a temporary file was created.
     */
    public static function ensureWebp(string $sourcePath): array
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return [$sourcePath, false];
        }

        // Check if already WebP
        $imgInfo = @getimagesize($sourcePath);
        if ($imgInfo && $imgInfo['mime'] === 'image/webp') {
            return [$sourcePath, false];
        }

        if (!$imgInfo || !function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return [$sourcePath, false];
        }

        try {
            $data = file_get_contents($sourcePath);
            $im = @imagecreatefromstring($data);
            if (!$im) {
                return [$sourcePath, false];
            }

            // Preserve alpha transparency for PNGs
            imagealphablending($im, true);
            imagesavealpha($im, true);

            $tempDir = sys_get_temp_dir();
            $webpPath = $tempDir . DIRECTORY_SEPARATOR . 'webp_' . uniqid() . '_' . time() . '.webp';
            $success = imagewebp($im, $webpPath, 85);
            imagedestroy($im);

            if ($success && file_exists($webpPath) && filesize($webpPath) > 0) {
                return [$webpPath, true];
            }
        } catch (\Throwable $e) {
            Log::warning('WebP auto-conversion failed: ' . $e->getMessage());
        }

        return [$sourcePath, false];
    }

    /**
     * Upload an image to Cloudinary (supports local file path, UploadedFile, or base64 raw string).
     * Automatically converts JPG/PNG to WebP before upload.
     * Returns the secure HTTPS URL on success, or null on failure.
     */
    public static function upload(string|UploadedFile $file, ?string $folder = null): ?string
    {
        if (!self::isConfigured()) {
            return null;
        }

        $cloudName = trim((string) Setting::get('adm_cloudinary_cloud_name'));
        $apiKey    = trim((string) Setting::get('adm_cloudinary_api_key'));
        $apiSecret = trim((string) Setting::get('adm_cloudinary_api_secret'));
        $folder    = $folder ?: Setting::get('adm_cloudinary_folder', 'aftabnagar_marketplace');

        $timestamp = time();

        // Build parameters to sign (sorted alphabetically)
        $params = [
            'folder'    => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($params);

        // Compute SHA-1 signature
        $signString = '';
        foreach ($params as $k => $v) {
            $signString .= ($signString ? '&' : '') . "{$k}={$v}";
        }
        $signature = sha1($signString . $apiSecret);

        $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

        $tempFilesToClean = [];

        try {
            $request = Http::timeout(45);

            if ($file instanceof UploadedFile) {
                [$targetPath, $isTemp] = self::ensureWebp($file->getRealPath());
                if ($isTemp) $tempFilesToClean[] = $targetPath;

                $fileContents = file_get_contents($targetPath);
                $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = Str::slug($origName) . '.webp';
                $request = $request->attach('file', $fileContents, $fileName);

            } elseif (is_string($file) && file_exists($file)) {
                [$targetPath, $isTemp] = self::ensureWebp($file);
                if ($isTemp) $tempFilesToClean[] = $targetPath;

                $fileContents = file_get_contents($targetPath);
                $origName = pathinfo($file, PATHINFO_FILENAME);
                $fileName = Str::slug($origName) . '.webp';
                $request = $request->attach('file', $fileContents, $fileName);

            } elseif (is_string($file) && str_starts_with($file, 'data:image')) {
                // Base64 data URI
                $payload = array_merge($params, [
                    'file'      => $file,
                    'api_key'   => $apiKey,
                    'signature' => $signature,
                ]);
                $response = $request->post($endpoint, $payload);
                return self::handleResponse($response);
            } else {
                return null;
            }

            $response = $request->post($endpoint, array_merge($params, [
                'api_key'   => $apiKey,
                'signature' => $signature,
            ]));

            return self::handleResponse($response);

        } catch (\Throwable $e) {
            Log::warning('Cloudinary upload error: ' . $e->getMessage());
            return null;
        } finally {
            foreach ($tempFilesToClean as $tf) {
                if (file_exists($tf)) {
                    @unlink($tf);
                }
            }
        }
    }

    /**
     * Delete an asset from Cloudinary by its URL or public_id.
     * Returns true on success or if already not found.
     */
    public static function delete(string $urlOrPublicId): bool
    {
        if (!self::isConfigured()) {
            return false;
        }

        $publicId = self::extractPublicId($urlOrPublicId);
        if (empty($publicId)) {
            return false;
        }

        $cloudName = trim((string) Setting::get('adm_cloudinary_cloud_name'));
        $apiKey    = trim((string) Setting::get('adm_cloudinary_api_key'));
        $apiSecret = trim((string) Setting::get('adm_cloudinary_api_secret'));

        $timestamp = time();

        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        ksort($params);

        $signString = '';
        foreach ($params as $k => $v) {
            $signString .= ($signString ? '&' : '') . "{$k}={$v}";
        }
        $signature = sha1($signString . $apiSecret);

        $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy";

        try {
            $response = Http::timeout(30)->post($endpoint, [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
                'api_key'   => $apiKey,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $result = $json['result'] ?? '';
                if ($result === 'ok' || $result === 'not found') {
                    return true;
                }
            }

            Log::warning('Cloudinary destroy response: ' . $response->body());
            return false;

        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract the Cloudinary public_id from a full URL.
     * e.g. "https://res.cloudinary.com/a3xw53zs/image/upload/v1792197398/aftabnagar_marketplace/tvhehmufgd7ks8tps5rc.webp"
     * -> "aftabnagar_marketplace/tvhehmufgd7ks8tps5rc"
     */
    public static function extractPublicId(string $url): string
    {
        $url = trim($url);

        // If it's already just a public_id (no http prefix)
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            // Strip any trailing file extension
            return preg_replace('/\.[^.]+$/', '', $url);
        }

        // Match Cloudinary URL pattern
        if (preg_match('#/image/upload/(?:v\d+/)?(.+?)(?:\.[a-zA-Z0-9]+)?$#i', $url, $matches)) {
            return $matches[1];
        }

        // Fallback: strip domain and query
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = preg_replace('#^/image/upload/(?:v\d+/)?#', '', $path);
        return preg_replace('/\.[^.]+$/', '', $path);
    }

    /**
     * Parse Cloudinary response and extract secure URL.
     */
    private static function handleResponse($response): ?string
    {
        if ($response && $response->successful()) {
            $json = $response->json();
            if (!empty($json['secure_url'])) {
                return $json['secure_url'];
            }
            if (!empty($json['url'])) {
                return $json['url'];
            }
        }

        if ($response) {
            Log::warning('Cloudinary upload rejected: ' . $response->body());
        }

        return null;
    }
}
