<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePilgrimPortalAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login');
        }

        if ($request->user()?->portalAccount()->exists()) {
            return $next($request);
        }

        abort(403, 'Akun ini tidak memiliki akses ke portal jamaah.');
    }
}
