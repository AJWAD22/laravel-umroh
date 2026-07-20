<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\RegisterDeviceTokenRequest;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\MobileDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return new ProfileResource(
            $request->user()->load(['branch', 'pilgrim', 'tourLeader', 'muthawwif', 'roles']),
        );
    }

    public function registerDeviceToken(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $device = MobileDevice::query()->updateOrCreate(
            ['device_uuid' => $data['device_uuid']],
            [
                ...$data,
                'user_id' => $request->user()->id,
                'activated_at' => now(),
                'last_used_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json([
            'message' => 'Perangkat siap menerima notifikasi.',
            'device_id' => $device->id,
        ]);
    }
}
