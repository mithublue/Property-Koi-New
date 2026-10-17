<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key with optional fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting key-value pair and clear cache.
     */
    public static function set(string $key, mixed $value): void
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting_{$key}");
    }

    /**
     * Check if a boolean setting is truthy.
     */
    public static function isTrue(string $key, bool $default = false): bool
    {
        $val = static::get($key, $default ? '1' : '0');
        return in_array($val, ['1', 1, 'yes', 'true', true], true);
    }
}
