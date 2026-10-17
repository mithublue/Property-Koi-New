<?php

namespace App\Http\Controllers;

use App\Services\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'nullable|image|max:10240', // 10MB max
            'image_base64' => 'nullable|string',
        ]);

        $uploadDir = public_path('uploads/properties');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'prop_' . date('Ymd_His') . '_' . Str::random(8) . '.webp';
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $relativeUrl = 'uploads/properties/' . $filename;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $tempPath = $file->getRealPath();
            $converted = WatermarkService::convertToWebp($tempPath, $destPath, true);

            if ($converted) {
                $cloudUrl = \App\Services\CloudinaryService::upload($destPath);
                if ($cloudUrl && file_exists($destPath)) {
                    @unlink($destPath);
                }
                $finalUrl = $cloudUrl ?: asset($relativeUrl);
                $finalPath = $cloudUrl ?: $relativeUrl;

                return response()->json([
                    'success' => true,
                    'url'     => $finalUrl,
                    'path'    => $finalPath,
                    'is_webp' => true,
                    'storage' => $cloudUrl ? 'cloudinary' : 'local',
                ]);
            }
        } elseif ($request->filled('image_base64')) {
            $base64 = $request->input('image_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $decoded = base64_decode($base64);
            if ($decoded !== false) {
                $tempFile = tempnam(sys_get_temp_dir(), 'clip_');
                file_put_contents($tempFile, $decoded);

                $converted = WatermarkService::convertToWebp($tempFile, $destPath, true);
                @unlink($tempFile);

                if ($converted) {
                    $cloudUrl = \App\Services\CloudinaryService::upload($destPath);
                    if ($cloudUrl && file_exists($destPath)) {
                        @unlink($destPath);
                    }
                    $finalUrl = $cloudUrl ?: asset($relativeUrl);
                    $finalPath = $cloudUrl ?: $relativeUrl;

                    return response()->json([
                        'success' => true,
                        'url'     => $finalUrl,
                        'path'    => $finalPath,
                        'is_webp' => true,
                        'storage' => $cloudUrl ? 'cloudinary' : 'local',
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'ছবি আপলোড বা WebP তে রূপান্তর করা সম্ভব হয়নি। অনুগ্রহ করে ফাইল ফরম্যাট চেক করুন।',
        ], 422);
    }
}
