<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'user_id',
        'vehicle_id',
        'service_id',
        'booking_date',
        'queue_order',
        'estimated_arrival_at',
        'status',
        'complaint',
        'cancel_reason',
        'checked_in_at',
        'started_at',
        'finished_at',
        'cancelled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'queue_order' => 'integer',
            'status' => BookingStatus::class,
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke Pelanggan (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Kendaraan yang diservis.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    /**
     * Relasi ke Jenis Layanan Servis.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relasi ke riwayat log audit perubahan status booking.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class)->orderBy('created_at', 'asc');
    }

    /**
     * Relasi ke catatan log notifikasi WhatsApp yang pernah dikirimkan.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope untuk mengambil hanya booking yang aktif menghitung kuota (selain status Cancelled).
     */
    public function scopeCountingQuota(Builder $query): Builder
    {
        return $query->where('status', '!=', BookingStatus::Cancelled->value);
    }

    /**
     * Scope untuk filter berdasarkan tanggal booking.
     */
    public function scopeOnDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $dateString = is_string($date) ? $date : $date->toDateString();

        return $query->where('booking_date', $dateString);
    }

    /**
     * Dapatkan nomor urut antrean berikutnya yang tersedia untuk tanggal tertentu.
     */
    public static function getNextQueueOrder(CarbonInterface|string $date): int
    {
        $dateString = is_string($date) ? $date : $date->toDateString();

        $maxQueue = (int) self::where('booking_date', $dateString)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->max('queue_order');

        return $maxQueue + 1;
    }

    /**
     * Hasilkan kode booking unik dengan format: AM-YYYYMMDD-XXX (contoh: AM-20261021-001).
     */
    public static function generateBookingCode(CarbonInterface|string $date, int $queueOrder): string
    {
        $parsedDate = is_string($date) ? Carbon::parse($date) : $date;

        return sprintf('AM-%s-%03d', $parsedDate->format('Ymd'), $queueOrder);
    }

    /**
     * Transisikan status booking secara aman, perbarui timestamp, dan catat riwayat log status.
     */
    public function transitionTo(BookingStatus $newStatus, ?int $changedById = null, ?string $note = null): void
    {
        $oldStatus = $this->status;

        if (! $oldStatus->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Transisi status dari {$oldStatus->label()} ke {$newStatus->label()} tidak diizinkan."
            );
        }

        $now = now();

        match ($newStatus) {
            BookingStatus::CheckedIn => $this->checked_in_at = $now,
            BookingStatus::InProgress => $this->started_at = $now,
            BookingStatus::Completed => $this->finished_at = $now,
            BookingStatus::Cancelled => $this->cancelled_at = $now,
            default => null,
        };

        $this->status = $newStatus;
        if ($newStatus === BookingStatus::Cancelled && $note !== null && empty($this->cancel_reason)) {
            $this->cancel_reason = $note;
        }

        $this->save();

        // Catat riwayat perubahan ke tabel booking_status_logs
        $this->statusLogs()->create([
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'changed_by' => $changedById,
            'note' => $note,
        ]);
    }
}
