<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'channel',
        'phone',
        'message',
        'opened_at',
        'sent_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke data pemesanan terkait.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relasi ke staf/pengirim notifikasi.
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
