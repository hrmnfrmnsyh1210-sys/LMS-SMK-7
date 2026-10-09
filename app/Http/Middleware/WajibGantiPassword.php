<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Arahkan user ke halaman ganti password jika masih memakai password default. */
class WajibGantiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->wajib_ganti_password) {
            return redirect()->route('profil.password')
                ->with('warning', 'Demi keamanan, silakan ganti password Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
