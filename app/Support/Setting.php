<?php

namespace App\Support;

use App\Models\WorkshopSetting;
use Illuminate\Support\Facades\Cache;

class Setting
{
    /**
     * Dapatkan nilai konfigurasi bengkel berdasarkan kunci (key) dengan caching 24 jam.
     * Nilai di-cast otomatis sesuai kolom 'type' (integer, boolean, json, string).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("workshop_setting_{$key}", 86400, function () use ($key, $default) {
            $setting = WorkshopSetting::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer', 'int' => (int) $setting->value,
                'boolean', 'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json', 'array' => json_decode((string) $setting->value, true),
                default => $setting->value ?? $default,
            };
        });
    }

    /**
     * Setel atau perbarui konfigurasi dan bersihkan cache terkait.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        WorkshopSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
            ]
        );

        Cache::forget("workshop_setting_{$key}");
    }
}
