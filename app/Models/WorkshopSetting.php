<?php

namespace App\Models;

use Database\Factories\WorkshopSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WorkshopSetting extends Model
{
    /** @use HasFactory<WorkshopSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Bersihkan cache secara otomatis saat setting diperbarui atau dihapus.
     */
    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            Cache::forget("workshop_setting_{$setting->key}");
        });

        static::deleted(function (self $setting) {
            Cache::forget("workshop_setting_{$setting->key}");
        });
    }
}
