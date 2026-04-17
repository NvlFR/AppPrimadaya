<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Memvalidasi apakah pengguna memiliki role yang diizinkan untuk mengakses route.
     *
     * @param  array<string, mixed>  $roles  Role yang diizinkan (contoh: 'admin', 'kasir')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Pastikan pengguna sudah login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role?->name;

        // Cek apakah role pengguna termasuk dalam daftar yang diizinkan
        if (! in_array($userRole, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
