<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\AdminNotificationCreated;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\SosReport;
use App\Models\User;
use App\Notifications\GeofenceExitAlert;
use App\Notifications\GpsOfflineAlert;
use App\Notifications\SosAlert;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public function __construct(private readonly FcmPushService $push) {}

    public function sos(SosReport $report): void
    {
        $report->loadMissing([
            'pilgrim:id,full_name,registration_number',
            'group.tourLeader.user',
            'group.muthawwif.user',
        ]);
        $message = "{$report->pilgrim->full_name} mengirim laporan darurat.";
        $this->send(
            $report->branch_id,
            'sos',
            [
                'title' => 'SOS Jamaah',
                'message' => $message,
                'pilgrim_id' => $report->pilgrim_id,
                'pilgrim_name' => $report->pilgrim->full_name,
                'sos_report_id' => $report->id,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'occurred_at' => $report->reported_at->toIso8601String(),
                'url' => route('monitoring.sos.show', $report),
            ],
            SosAlert::class,
        );

        $staff = collect([
            $report->group?->tourLeader?->user,
            $report->group?->muthawwif?->user,
        ])->filter();
        $this->push->sendToUsers(
            $this->recipients($report->branch_id)->concat($staff)->unique('id')->values(),
            'SOS Jamaah',
            $message,
            [
                'type' => 'sos',
                'sos_report_id' => $report->id,
                'pilgrim_id' => $report->pilgrim_id,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
            ],
        );
    }

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
