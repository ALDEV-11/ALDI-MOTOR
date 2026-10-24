<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Otorisasi melihat daftar pemesanan sendiri.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Otorisasi melihat detail pemesanan (hanya pemilik atau admin).
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->isAdmin();
    }

    /**
     * Otorisasi membuat pemesanan servis (pengguna tidak boleh sedang dalam status diblokir).
     */
    public function create(User $user): bool
    {
        return ! $user->isBlocked();
    }

    /**
     * Otorisasi pembatalan pemesanan oleh pelanggan.
     * Hanya pemilik pemesanan atau admin, dan HANYA untuk status yang berstatus 'confirmed' (terkonfirmasi).
     */
    public function cancel(User $user, Booking $booking): bool
    {
        $isOwnerOrAdmin = ($user->id === $booking->user_id) || $user->isAdmin();

        return $isOwnerOrAdmin && ($booking->status === BookingStatus::Confirmed);
    }
}
