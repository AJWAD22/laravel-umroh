<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        foreach ($roles as $role) {
            if ($user?->hasRole($role) && $user->tokenCan("mobile:{$role}")) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Anda tidak memiliki akses untuk endpoint ini.',
        ], 403);
    }
}
