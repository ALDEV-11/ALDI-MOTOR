<?php

namespace App\Models;

use App\Support\Setting;
use Carbon\CarbonInterface;
use Database\Factories\DailyCapacityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCapacity extends Model
{
    /** @use HasFactory<DailyCapacityFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'quota',
        'is_open',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'quota' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    /**
     * Dapatkan kuota efektif untuk tanggal tertentu (memperhitungkan override harian atau default setting).
     */
    public static function getEffectiveQuota(CarbonInterface|string $date): int
    {
        $dateString = is_string($date) ? $date : $date->toDateString();
        $record = self::whereDate('date', $dateString)->first();

        if ($record) {
            if (! $record->is_open) {
                return 0; // Bengkel tutup / libur
            }

            if ($record->quota !== null) {
                return $record->quota;
            }
        }

        // Ambil kuota default bengkel dari Setting (default 10 jika belum diatur)
        return (int) Setting::get('daily_quota', 10);
    }

    /**
     * Cek apakah bengkel buka pada tanggal tertentu.
     */
    public static function isOpenOn(CarbonInterface|string $date): bool
    {
        $dateString = is_string($date) ? $date : $date->toDateString();
        $record = self::whereDate('date', $dateString)->first();

        return $record ? $record->is_open : true;
    }
}
