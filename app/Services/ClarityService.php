<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class ClarityService
{
    /**
     * Get the configured Microsoft Clarity Project ID.
     */
    public static function getProjectId(): ?string
    {
        $id = Setting::get('adm_clarity_project_id') ?: Setting::get('clarity_project_id');
        if (!empty($id)) {
            return trim((string) $id);
        }

        $envId = config('services.clarity.project_id') ?: env('MICROSOFT_CLARITY_ID');
        return !empty($envId) ? trim((string) $envId) : null;
    }

    /**
     * Normalize truthy setting values ('yes', '1', 'true', true).
     */
    private static function isTruthy(mixed $val): bool
    {
        if (is_bool($val)) {
            return $val;
        }
        return in_array(strtolower(trim((string) $val)), ['yes', '1', 'true', 'on'], true);
    }

    /**
     * Check if Clarity tracking is actively enabled.
     */
    public static function isEnabled(): bool
    {
        return self::isTruthy(Setting::get('adm_clarity_enabled', 'yes')) && !empty(self::getProjectId());
    }

    /**
     * Check if a project ID is configured.
     */
    public static function isConfigured(): bool
    {
        return !empty(self::getProjectId());
    }

    /**
     * Check if admin exclusion toggle is enabled.
     */
    public static function shouldExcludeAdmin(): bool
    {
        return self::isTruthy(Setting::get('adm_clarity_exclude_admin', 'yes'));
    }

    /**
     * Determine if the current session should be excluded from recording.
     */
    public static function isCurrentSessionExcluded(): bool
    {
        if (!self::shouldExcludeAdmin()) {
            return false;
        }

        if (Auth::check()) {
            $user = Auth::user();
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
            if (in_array($user->role ?? '', ['admin', 'administrator', 'editor'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user role and user identification should be tracked.
     */
    public static function shouldTrackRoles(): bool
    {
        return self::isTruthy(Setting::get('adm_clarity_track_roles', 'yes'));
    }

    /**
     * Check if input masking is enabled.
     */
    public static function shouldMaskInputs(): bool
    {
        return self::isTruthy(Setting::get('adm_clarity_mask_inputs', 'yes'));
    }

    /**
     * Determine if the Clarity script should be rendered on the current request.
     */
    public static function shouldRender(): bool
    {
        return self::isEnabled() && !self::isCurrentSessionExcluded();
    }

    /**
     * Get the Microsoft Clarity project dashboard URL.
     */
    public static function getDashboardUrl(): string
    {
        $id = self::getProjectId();
        return $id ? "https://clarity.microsoft.com/projects/view/{$id}/dashboard" : "https://clarity.microsoft.com";
    }

    /**
     * Get the Microsoft Clarity recordings URL.
     */
    public static function getRecordingsUrl(): string
    {
        $id = self::getProjectId();
        return $id ? "https://clarity.microsoft.com/projects/view/{$id}/recordings" : "https://clarity.microsoft.com";
    }

    /**
     * Get the Microsoft Clarity heatmaps URL.
     */
    public static function getHeatmapsUrl(): string
    {
        $id = self::getProjectId();
        return $id ? "https://clarity.microsoft.com/projects/view/{$id}/heatmaps" : "https://clarity.microsoft.com";
    }

    /**
     * Get the Microsoft Clarity project settings URL.
     */
    public static function getSettingsUrl(): string
    {
        $id = self::getProjectId();
        return $id ? "https://clarity.microsoft.com/projects/view/{$id}/settings" : "https://clarity.microsoft.com";
    }

    /**
     * Get the embedded iframe base URL matching WordPress Clarity plugin contract.
     */
    public static function getEmbedUrl(): string
    {
        $id = self::getProjectId();
        $siteUrl = rawurlencode(config('app.url', url('/')));
        $base = "https://clarity.microsoft.com/embed?integration=Wordpress&siteurl={$siteUrl}";
        return $id ? "{$base}&project={$id}" : $base;
    }
}
