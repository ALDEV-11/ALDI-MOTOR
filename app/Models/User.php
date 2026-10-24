<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'role', 'whatsapp_opt_in', 'no_show_count', 'blocked_until', 'aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'whatsapp_opt_in' => 'boolean',
            'no_show_count' => 'integer',
            'blocked_until' => 'datetime',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Cek apakah pengguna memiliki peran Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Cek apakah akun pengguna sedang dalam status diblokir sementara.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_until !== null && $this->blocked_until->isFuture();
    }

    /**
     * Relasi ke daftar kendaraan bermotor milik pengguna.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Relasi ke daftar riwayat pemesanan servis (Booking).
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
