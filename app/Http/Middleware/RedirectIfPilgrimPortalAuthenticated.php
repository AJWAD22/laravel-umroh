<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPilgrimPortalAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        return $request->user()->portalAccount()->exists()
            ? redirect()->route('portal.dashboard')
            : redirect()->route('dashboard');
    }
}
