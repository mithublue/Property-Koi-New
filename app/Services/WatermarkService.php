<?php

namespace App\Services;

use App\Models\Setting;

class WatermarkService
{
    /**
     * Check if watermark is globally enabled
     */
    public static function isEnabled(): bool
    {
        try {
            return Setting::get('adm_watermark_enabled', 'yes') === 'yes';
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Apply watermark to an image file (supports JPG, PNG, WEBP).
     * Modifies the file in-place.
     */
    public static function applyWatermark(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath) || !is_writable($filePath)) {
            return false;
        }

        if (!extension_loaded('gd')) {
            return false;
        }

        if (!self::isEnabled()) {
            return true;
        }

        $info = @getimagesize($filePath);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'];
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

        self::renderConfiguredWatermark($image, $srcW, $srcH);

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

        if ($withWatermark && self::isEnabled() && $srcW >= 220 && $srcH >= 150) {
            self::renderConfiguredWatermark($image, $srcW, $srcH);
        }

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $success = @imagewebp($image, $destinationPath, 88);
        @imagedestroy($image);

        return (bool) $success;
    }

    /**
     * Dispatch watermark rendering based on system settings (image logo or text badge)
     */
    private static function renderConfiguredWatermark(&$image, int $srcW, int $srcH): void
    {
        try {
            $type = Setting::get('adm_watermark_type', 'text');
            $logoPath = Setting::get('adm_watermark_logo', '');
            $position = Setting::get('adm_watermark_position', 'bottom-right');
            $opacity = (int) Setting::get('adm_watermark_opacity', 82);
            $scale = (int) Setting::get('adm_watermark_scale', 20); // 10% - 40% width

            // If logo mode is requested and logo file exists, render logo watermark
            if (($type === 'image' || !empty($logoPath)) && !empty($logoPath)) {
                $absoluteLogo = public_path($logoPath);
                if (file_exists($absoluteLogo) && is_readable($absoluteLogo)) {
                    $logoRendered = self::renderLogoWatermark($image, $srcW, $srcH, $absoluteLogo, $position, $opacity, $scale);
                    if ($logoRendered) {
                        return;
                    }
                }
            }

            // Fallback or explicit text watermark
            $text = Setting::get('adm_watermark_text', 'PropertyKoi.com');
            $fontPath = self::getTtfFontPath();
            $fontSize = max(13, min(36, (int) round($srcW * 0.022)));

            if ($fontPath && function_exists('imagettfbbox') && function_exists('imagettftext')) {
                self::renderTtfWatermark($image, $srcW, $srcH, $text, $fontPath, $fontSize, $position, $opacity);
            }
        } catch (\Throwable $e) {
            // Gracefully ignore rendering errors so image upload never fails
        }
    }

    /**
     * Render transparent/custom PNG, WEBP, or JPG image logo watermark onto target image
     */
    public static function renderLogoWatermark(
        &$image,
        int $srcW,
        int $srcH,
        string $logoFile,
        string $position = 'bottom-right',
        int $opacity = 82,
        int $scale = 20
    ): bool {
        $info = @getimagesize($logoFile);
        if (!$info) {
            return false;
        }

        $logoW = $info[0];
        $logoH = $info[1];
        if ($logoW <= 0 || $logoH <= 0) {
            return false;
        }

        $logoImg = null;
        switch ($info['mime']) {
            case 'image/png':
                $logoImg = @imagecreatefrompng($logoFile);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $logoImg = @imagecreatefromwebp($logoFile);
                }
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $logoImg = @imagecreatefromjpeg($logoFile);
                break;
        }

        if (!$logoImg) {
            return false;
        }

        // Calculate scaled dimensions: proportional scaling based on width percentage
        $scalePct = max(8, min(50, $scale)) / 100.0;
        $targetW = (int) round($srcW * $scalePct);
        $aspectRatio = $logoH / $logoW;
        $targetH = (int) round($targetW * $aspectRatio);

        // Cap height if too large
        $maxH = (int) round($srcH * 0.35);
        if ($targetH > $maxH) {
            $targetH = $maxH;
            $targetW = (int) round($targetH / $aspectRatio);
        }

        $margin = max(12, (int) round($srcW * 0.025));

        switch ($position) {
            case 'top-left':
                $posX = $margin;
                $posY = $margin;
                break;
            case 'top-right':
                $posX = $srcW - $targetW - $margin;
                $posY = $margin;
                break;
            case 'bottom-left':
                $posX = $margin;
                $posY = $srcH - $targetH - $margin;
                break;
            case 'center':
                $posX = (int) round(($srcW - $targetW) / 2);
                $posY = (int) round(($srcH - $targetH) / 2);
                break;
            case 'bottom-right':
            default:
                $posX = $srcW - $targetW - $margin;
                $posY = $srcH - $targetH - $margin;
                break;
        }

        $posX = max(5, min($posX, $srcW - $targetW - 5));
        $posY = max(5, min($posY, $srcH - $targetH - 5));

        // Create resampled canvas for the scaled logo
        $scaledLogo = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($scaledLogo, false);
        imagesavealpha($scaledLogo, true);
        $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
        imagefilledrectangle($scaledLogo, 0, 0, $targetW, $targetH, $transparent);

        imagecopyresampled($scaledLogo, $logoImg, 0, 0, 0, 0, $targetW, $targetH, $logoW, $logoH);
        imagedestroy($logoImg);

        // Handle opacity: if < 100%, adjust alpha channel of each pixel
        $opacityPct = max(10, min(100, $opacity)) / 100.0;
        if ($opacityPct < 0.99) {
            self::applyAlphaOpacity($scaledLogo, $targetW, $targetH, $opacityPct);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagecopy($image, $scaledLogo, $posX, $posY, 0, 0, $targetW, $targetH);
        imagedestroy($scaledLogo);

        return true;
    }

    /**
     * Helper to apply custom opacity factor (0.1 to 1.0) to a truecolor GD image with alpha
     */
    private static function applyAlphaOpacity(&$im, int $w, int $h, float $factor): void
    {
        imagealphablending($im, false);
        imagesavealpha($im, true);

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $color = imagecolorat($im, $x, $y);
                $alpha = ($color >> 24) & 0x7F; // 0 (opaque) to 127 (transparent)

                if ($alpha >= 127) {
                    continue; // fully transparent pixel
                }

                // Increase transparency according to opacity factor
                // 127 - (127 - originalAlpha) * factor
                $newAlpha = 127 - (int) round((127 - $alpha) * $factor);
                $newAlpha = max(0, min(127, $newAlpha));

                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $newColor = imagecolorallocatealpha($im, $r, $g, $b, $newAlpha);
                imagesetpixel($im, $x, $y, $newColor);
            }
        }
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

    private static function renderTtfWatermark(
        &$image,
        int $srcW,
        int $srcH,
        string $text,
        string $fontPath,
        int $fontSize,
        string $position = 'bottom-right',
        int $opacity = 82
    ): void {
        $bbox = @imagettfbbox($fontSize, 0, $fontPath, $text);
        if (!$bbox) return;

        $textW = abs($bbox[2] - $bbox[0]);
        $textH = abs($bbox[7] - $bbox[1]);

        $padX = 14;
        $padY = 8;
        $boxW = $textW + ($padX * 2);
        $boxH = $textH + ($padY * 2);

        $margin = 16;

        switch ($position) {
            case 'top-left':
                $posX = $margin;
                $posY = $margin;
                break;
            case 'top-right':
                $posX = $srcW - $boxW - $margin;
                $posY = $margin;
                break;
            case 'bottom-left':
                $posX = $margin;
                $posY = $srcH - $boxH - $margin;
                break;
            case 'center':
                $posX = (int) round(($srcW - $boxW) / 2);
                $posY = (int) round(($srcH - $boxH) / 2);
                break;
            case 'bottom-right':
            default:
                $posX = $srcW - $boxW - $margin;
                $posY = $srcH - $boxH - $margin;
                break;
        }

        if ($posX < 0) $posX = 10;
        if ($posY < 0) $posY = 10;

        // Calculate alpha based on opacity (0 = transparent, 127 = fully transparent in GD)
        // Opacity 100% -> alpha 0; Opacity 50% -> alpha ~64; Opacity 80% -> alpha ~25
        $gdAlpha = (int) round((100 - max(20, min(100, $opacity))) * 1.27);

        // Draw translucent dark pill badge background
        $badge = imagecreatetruecolor($boxW, $boxH);
        imagealphablending($badge, false);
        imagesavealpha($badge, true);

        $badgeBg = imagecolorallocatealpha($badge, 15, 23, 42, min(127, $gdAlpha + 20)); // Dark Slate #0f172a with opacity
        imagefilledrectangle($badge, 0, 0, $boxW, $boxH, $badgeBg);

        imagealphablending($image, true);
        imagecopy($image, $badge, $posX, $posY, 0, 0, $boxW, $boxH);
        imagedestroy($badge);

        // Draw text in white with alpha
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, $gdAlpha);
        $textX = $posX + $padX;
        $textY = $posY + $padY + $textH;

        @imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $text);
    }
}
