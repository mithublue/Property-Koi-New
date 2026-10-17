<?php

namespace App\Services;

class WatermarkService
{
    /**
     * Apply watermark to an image file (supports JPG, PNG, WEBP).
     * Modifies the file in-place or saves as WebP.
     */
    public static function applyWatermark(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath) || !is_writable($filePath)) {
            return false;
        }

        if (!extension_loaded('gd')) {
            return false;
        }

        $info = @getimagesize($filePath);
        if (!$info) {
            return false;
        }

        $mime  = $info['mime'];
        $srcW = $info[0];
        $srcH = $info[1];

        // Skip tiny icons
        if ($srcW < 220 || $srcH < 150) {
            return false;
        }

        $image = null;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = @imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($filePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($filePath);
                }
                break;
        }

        if (!$image) {
            return false;
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $text = 'PropertyKoi.com';
        $fontPath = self::getTtfFontPath();
        $fontSize = max(13, min(36, (int) round($srcW * 0.022)));

        if ($fontPath && function_exists('imagettfbbox') && function_exists('imagettftext')) {
            self::renderTtfWatermark($image, $srcW, $srcH, $text, $fontPath, $fontSize);
        }

        $saved = false;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $saved = @imagejpeg($image, $filePath, 90);
                break;
            case 'image/png':
                $saved = @imagepng($image, $filePath, 8);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $saved = @imagewebp($image, $filePath, 88);
                }
                break;
        }

        @imagedestroy($image);
        return (bool) $saved;
    }

    /**
     * Convert any image to WebP with watermark
     */
    public static function convertToWebp(string $sourcePath, string $destinationPath, bool $withWatermark = true): bool
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'];
        $image = null;

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($sourcePath);
                break;
        }

        if (!$image) {
            return false;
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $srcW = imagesx($image);
        $srcH = imagesy($image);

        if ($withWatermark && $srcW >= 220 && $srcH >= 150) {
            $fontPath = self::getTtfFontPath();
            $fontSize = max(13, min(36, (int) round($srcW * 0.022)));
            if ($fontPath && function_exists('imagettfbbox') && function_exists('imagettftext')) {
                self::renderTtfWatermark($image, $srcW, $srcH, 'PropertyKoi.com', $fontPath, $fontSize);
            }
        }

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $success = @imagewebp($image, $destinationPath, 88);
        @imagedestroy($image);

        return (bool) $success;
    }

    public static function getTtfFontPath(): ?string
    {
        $bundled = public_path('assets/fonts/watermark.ttf');
        $candidates = [
            $bundled,
            'C:/Windows/Fonts/verdana.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        foreach ($candidates as $c) {
            if (!empty($c) && file_exists($c) && is_readable($c)) {
                return $c;
            }
        }

        return null;
    }

    private static function renderTtfWatermark(&$image, int $srcW, int $srcH, string $text, string $fontPath, int $fontSize): void
    {
        $bbox = @imagettfbbox($fontSize, 0, $fontPath, $text);
        if (!$bbox) return;

        $textW = abs($bbox[2] - $bbox[0]);
        $textH = abs($bbox[7] - $bbox[1]);

        $padX = 14;
        $padY = 8;
        $boxW = $textW + ($padX * 2);
        $boxH = $textH + ($padY * 2);

        $margin = 16;
        $posX = $srcW - $boxW - $margin;
        $posY = $srcH - $boxH - $margin;

        if ($posX < 0) $posX = 10;
        if ($posY < 0) $posY = 10;

        // Draw translucent dark pill badge background
        $badge = imagecreatetruecolor($boxW, $boxH);
        imagealphablending($badge, false);
        imagesavealpha($badge, true);

        $badgeBg = imagecolorallocatealpha($badge, 15, 23, 42, 40); // Dark Slate #0f172a with opacity
        imagefilledrectangle($badge, 0, 0, $boxW, $boxH, $badgeBg);

        imagealphablending($image, true);
        imagecopy($image, $badge, $posX, $posY, 0, 0, $boxW, $boxH);
        imagedestroy($badge);

        // Draw text in white
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $textX = $posX + $padX;
        $textY = $posY + $padY + $textH;

        @imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $text);
    }
}
