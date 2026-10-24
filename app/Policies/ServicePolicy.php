<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Otorisasi melihat daftar layanan di admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Otorisasi melihat detail layanan.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Otorisasi menambah layanan baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Otorisasi memperbarui data layanan.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Otorisasi menghapus layanan.
     */
    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
