<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'price_estimate',
        'reminder_interval_days',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price_estimate' => 'integer',
            'reminder_interval_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope untuk mengambil hanya layanan yang aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Format harga ke standar mata uang Rupiah.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format($this->price_estimate, 0, ',', '.');
    }

    /**
     * Relasi ke riwayat pemesanan servis.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
