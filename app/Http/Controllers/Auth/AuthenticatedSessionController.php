<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman formulir login.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Proses autentikasi login pengguna.
     * Mengarahkan admin ke /admin dan pelanggan ke /beranda.
     * Memeriksa status pemblokiran akun (blocked_until).
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        $user = Auth::user();

        // 1. Cek apakah akun sedang diblokir sementara (blocked_until masih di masa depan)
        if ($user->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $formattedDate = $user->blocked_until
                ->timezone('Asia/Jakarta')
                ->translatedFormat('d F Y \p\u\k\u\l H:i');

            throw ValidationException::withMessages([
                'email' => "Akun Anda sedang diblokir sementara hingga {$formattedDate} WIB. Silakan hubungi admin bengkel.",
            ]);
        }

        // 2. Cek apakah akun aktif
        if (! $user->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Silakan hubungi admin bengkel.',
            ]);
        }

        $request->session()->regenerate();

        // 3. Pengalihan cerdas sesuai peran
        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/beranda');
    }

    /**
     * Proses keluar (logout) dari sesi aktif.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
