<?php

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Admin = 'admin';

    /**
     * Dapatkan label yang ramah pengguna dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Pelanggan',
            self::Admin => 'Administrator',
        };
    }
}
