<?php

namespace App\Policies;

use App\Models\User;

class WorkshopSettingPolicy
{
    /**
     * Otorisasi melihat halaman konfigurasi bengkel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Otorisasi memperbarui konfigurasi bengkel.
     */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
