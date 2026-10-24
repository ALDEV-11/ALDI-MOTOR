<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'plate_number',
        'brand',
        'model',
        'year',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    /**
     * Relasi ke pemilik kendaraan (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke riwayat pemesanan servis (Booking).
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
