<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\AdminNotificationCreated;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\User;
use App\Notifications\GeofenceExitAlert;
use App\Notifications\GpsOfflineAlert;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public function __construct(private readonly FcmPushService $push) {}

    public function gpsOffline(PilgrimLocation $location): void
    {
        $location->loadMissing('pilgrim:id,branch_id,full_name');
        $this->send(
            $location->pilgrim->branch_id,
            'gps_offline',
            [
                'title' => 'GPS Offline',
                'message' => "Perangkat {$location->pilgrim->full_name} tidak mengirim posisi.",
                'pilgrim_id' => $location->pilgrim_id,
                'pilgrim_name' => $location->pilgrim->full_name,
                'last_seen_at' => $location->recorded_at->toIso8601String(),
                'occurred_at' => now()->toIso8601String(),
                'url' => route('monitoring.map.index'),
            ],
            GpsOfflineAlert::class,
        );
    }

    public function geofenceExit(
        Pilgrim $pilgrim,
        float $latitude,
        float $longitude,
        string $geofenceName,
    ): void {
        $this->send(
            $pilgrim->branch_id,
            'geofence_exit',
            [
                'title' => 'Keluar Geofence',
                'message' => "{$pilgrim->full_name} keluar dari area {$geofenceName}.",
                'pilgrim_id' => $pilgrim->id,
                'pilgrim_name' => $pilgrim->full_name,
                'geofence_name' => $geofenceName,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'occurred_at' => now()->toIso8601String(),
                'url' => route('monitoring.map.index'),
            ],
            GeofenceExitAlert::class,
        );
    }

    /**
     * @param  class-string<Notification>  $notificationClass
     */
    private function send(int $branchId, string $type, array $payload, string $notificationClass): void
    {
        $this->recipients($branchId)->each(
            fn (User $recipient) => $recipient->notify(new $notificationClass($branchId, $payload)),
        );

        AdminNotificationCreated::dispatch($branchId, $type, $payload);
    }

    private function recipients(int $branchId): Collection
    {
        $superAdmins = User::query()
            ->active()
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::SuperAdmin->value))
            ->get();
        $branchAdmins = User::query()
            ->active()
            ->where('branch_id', $branchId)
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::BranchAdmin->value))
            ->get();

        return $superAdmins->concat($branchAdmins)->unique('id')->values();
    }
}
