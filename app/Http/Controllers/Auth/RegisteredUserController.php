<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IndonesianPhone;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran pelanggan baru.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Simpan pendaftaran pelanggan baru.
     * Aturan ketat:
     * - Role selalu customer (PHP Enum Role::Customer) dan diabaikan jika ada di request.
     * - Nomor WhatsApp divalidasi dengan IndonesianPhone dan dinormalisasi ke format 628xxx.
     * - Pesan validasi dalam Bahasa Indonesia.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', new IndonesianPhone, 'unique:users,phone'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string' => 'Nama lengkap harus berupa teks.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar di sistem.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar di sistem.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $normalizedPhone = Phone::normalize($request->phone);

        // Role mutlak dipaksa di sisi server sebagai Customer
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $normalizedPhone,
            'role' => Role::Customer,
            'whatsapp_opt_in' => $request->boolean('whatsapp_opt_in', true),
            'no_show_count' => 0,
            'aktif' => true,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Pelanggan diarahkan langsung ke halaman /beranda
        return redirect()->intended('/beranda');
    }
}
