<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\IndonesianPhone;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman pengaturan profil pengguna.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
        ]);
    }

    /**
     * Perbarui informasi profil pengguna (nama, email, nomor WhatsApp, opt-in notifikasi).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                new IndonesianPhone,
                Rule::unique(User::class)->ignore($user->id),
            ],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah digunakan akun lain.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.unique' => 'Nomor WhatsApp ini sudah digunakan akun lain.',
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => Phone::normalize($validated['phone']),
            'whatsapp_opt_in' => $request->boolean('whatsapp_opt_in'),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profil Anda berhasil diperbarui.');
    }

    /**
     * Hapus akun pengguna secara permanen.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Kata sandi saat ini wajib diisi untuk konfirmasi.',
            'password.current_password' => 'Kata sandi yang Anda masukkan salah.',
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
