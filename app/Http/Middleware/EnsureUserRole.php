<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Pastikan pengguna memiliki peran yang sesuai dan akun tidak terblokir.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Blokir akun: jika blocked_until di masa depan, tolak akses dan keluarkan sesi
        if ($user->isBlocked()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $formattedDate = $user->blocked_until
                ->timezone('Asia/Jakarta')
                ->translatedFormat('d F Y \p\u\k\u\l H:i');

            return redirect()->route('login')->withErrors([
                'email' => "Akun Anda sedang diblokir sementara hingga {$formattedDate} WIB. Silakan hubungi admin bengkel.",
            ]);
        }

        // Cek kesesuaian peran
        $userRole = $user->role instanceof Role ? $user->role->value : (string) $user->role;

        if ($userRole !== $role) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
