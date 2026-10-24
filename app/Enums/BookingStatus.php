<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Dapatkan label status dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Konfirmasi',
            self::Confirmed => 'Dikonfirmasi',
            self::CheckedIn => 'Sudah Check-In',
            self::InProgress => 'Sedang Dikerjakan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Dapatkan kelas warna badge (Tailwind CSS) untuk representasi visual UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 border-amber-200',
            self::Confirmed => 'bg-blue-100 text-blue-800 border-blue-200',
            self::CheckedIn => 'bg-purple-100 text-purple-800 border-purple-200',
            self::InProgress => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::Completed => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::Cancelled => 'bg-rose-100 text-rose-800 border-rose-200',
        };
    }

    /**
     * Periksa apakah status ini dihitung dalam batas kuota harian bengkel.
     * Hanya status Cancelled yang membebaskan slot kuota kembali.
     */
    public function countsTowardQuota(): bool
    {
        return match ($this) {
            self::Cancelled => false,
            default => true,
        };
    }

    /**
     * Daftar transisi status yang valid dari status saat ini.
     *
     * @return array<int, BookingStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::CheckedIn, self::Cancelled],
            self::CheckedIn => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    /**
     * Periksa apakah transisi ke target status diizinkan.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
