<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MobileRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\LoginRequest;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->with(['branch', 'pilgrim', 'tourLeader', 'muthawwif', 'roles'])
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! $user->is_active || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => ['Email atau password tidak valid.']]);
        }

        $role = collect(MobileRole::cases())->first(function (MobileRole $role) use ($user): bool {
            $profile = match ($role) {
                MobileRole::Pilgrim => $user->pilgrim,
                MobileRole::TourLeader => $user->tourLeader,
                MobileRole::Muthawwif => $user->muthawwif,
            };

            return $user->hasRole($role->value)
                && $profile !== null
                && ($role === MobileRole::Pilgrim || $profile->is_active);
        });

        if (! $role) {
            throw ValidationException::withMessages(['email' => ['Akun tidak memiliki akses aplikasi mobile.']]);
        }

        $deviceName = $request->validated('device_name') ?: 'mobile-app';
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName, [$role->ability()]);

        return response()->json([
            'message' => 'Login berhasil.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'role' => $role->value,
            'user' => new ProfileResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
