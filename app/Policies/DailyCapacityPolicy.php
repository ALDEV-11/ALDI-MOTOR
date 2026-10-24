<?php

namespace App\Policies;

use App\Models\User;

class DailyCapacityPolicy
{
    /**
     * Otorisasi melihat kalender kapasitas harian.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Otorisasi mengatur kuota dan status operasional harian bengkel.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
