<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsReporter
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->isAdmin()) {
            abort(403, 'Halaman ini khusus untuk Pelapor.');
        }

        return $next($request);
    }
}
