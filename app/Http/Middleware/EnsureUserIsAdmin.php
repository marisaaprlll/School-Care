<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi halaman Antrean Laporan & Data Master hanya untuk role 'admin'.
 * Didaftarkan sebagai alias 'admin' di bootstrap/app.php.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Halaman ini khusus untuk Admin / Petugas Sarpras.');
        }

        return $next($request);
    }
}
