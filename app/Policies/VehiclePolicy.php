<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Otorisasi melihat daftar kendaraan sendiri.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Otorisasi melihat data detail kendaraan (hanya pemilik atau admin).
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->id === $vehicle->user_id || $user->isAdmin();
    }

    /**
     * Otorisasi mendaftarkan kendaraan baru.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Otorisasi memperbarui data kendaraan (hanya pemilik atau admin).
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->id === $vehicle->user_id || $user->isAdmin();
    }

    /**
     * Otorisasi menghapus kendaraan (hanya pemilik atau admin).
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->id === $vehicle->user_id || $user->isAdmin();
    }
}
