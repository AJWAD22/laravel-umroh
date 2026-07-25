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

        if ($request->user()?->canAccessAdminPanel()) {
            return redirect()->route('dashboard')
                ->with('error', 'Link tersebut khusus portal jamaah. Anda sudah diarahkan ke dashboard sesuai role akun.');
        }

        abort(403, 'Akun ini tidak memiliki akses ke portal jamaah.');
    }
}
