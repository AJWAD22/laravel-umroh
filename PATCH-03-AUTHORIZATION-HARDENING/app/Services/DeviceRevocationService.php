<?php
namespace App\Services;

use App\Models\MobileDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeviceRevocationService
{
    public function revokeAll(User $user, string $reason = 'security_action'): int
    {
        return DB::transaction(function () use ($user, $reason) {
            $devices = MobileDevice::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->get();

            foreach ($devices as $device) {
                $user->tokens()->where('name', 'activation-'.$device->device_uuid)->delete();
                $device->forceFill([
                    'revoked_at' => now(),
                    'revoked_reason' => $reason,
                ])->save();
            }

            return $devices->count();
        });
    }
}
