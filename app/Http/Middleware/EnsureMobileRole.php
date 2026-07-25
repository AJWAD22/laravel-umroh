<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Jika request membawa Authorization header (bearer token), anggap ini
        // permintaan API — cek token terlebih dahulu.
        if ($request->bearerToken()) {
            $bearer = $request->bearerToken();

            // Cari token personal access secara langsung supaya tidak tergantung
            // pada mekanisme guard yang mungkin juga mengembalikan session user.
            $patClass = \Laravel\Sanctum\PersonalAccessToken::class;
            $personal = $patClass::findToken($bearer);

            if (! $personal) {
                // Token tidak ditemukan / sudah dicabut
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $tokenUser = $personal->tokenable;

            // Pastikan objek user mengenal access token ini sehingga helper
            // currentAccessToken() mengembalikan instance yang benar.
            if (method_exists($tokenUser, 'withAccessToken')) {
                $tokenUser->withAccessToken($personal);
            }

            foreach ($roles as $role) {
                if ($tokenUser->hasRole($role) && $personal->can("mobile:{$role}")) {
                    // Lampirkan user ke request agar controller dapat memanggil $request->user()
                    $request->setUserResolver(fn () => $tokenUser);

                    return $next($request);
                }
            }

            return response()->json(['message' => 'Anda tidak memiliki akses untuk endpoint ini.'], 403);
        }

        // Non-API request (mis. session-based). Periksa role akun yang sedang login.
        $user = $request->user();
        foreach ($roles as $role) {
            if ($user?->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Anda tidak memiliki akses untuk endpoint ini.'], 403);
    }
}
