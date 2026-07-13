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

    public function sos(SosReport $report): void
    {
        $report->loadMissing([
            'pilgrim:id,branch_id,registration_number,full_name,phone',
            'group:id,name,code,tour_leader_id,muthawwif_id',
            'group.tourLeader:id,user_id,full_name',
            'group.tourLeader.user:id,branch_id,name',
            'group.muthawwif:id,user_id,full_name',
            'group.muthawwif.user:id,branch_id,name',
        ]);

        $payload = [
            'title' => 'SOS Jamaah',
            'message' => "{$report->pilgrim->full_name} mengirim laporan darurat.",
            'sos_report_id' => $report->id,
            'pilgrim_id' => $report->pilgrim_id,
            'pilgrim_name' => $report->pilgrim->full_name,
            'registration_number' => $report->pilgrim->registration_number,
            'group_id' => $report->group_id,
            'group_name' => $report->group?->name,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'occurred_at' => $report->reported_at?->toIso8601String() ?? now()->toIso8601String(),
            'url' => route('monitoring.sos.show', $report),
        ];

        $this->send($report->branch_id, 'sos', $payload, SosAlert::class);

        $staffRecipients = collect([
            $report->group?->tourLeader?->user,
            $report->group?->muthawwif?->user,
        ])->filter()->unique('id')->values();

        $this->push->sendToUsers(
            $staffRecipients,
            'SOS Jamaah',
            "{$report->pilgrim->full_name} membutuhkan bantuan.",
            [
                'type' => 'sos',
                'sos_report_id' => $report->id,
                'pilgrim_id' => $report->pilgrim_id,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
            ],
        );
    }

    public function sosAcknowledged(SosReport $report): void
    {
        $report->loadMissing([
            'pilgrim:id,user_id,full_name',
            'pilgrim.user:id,name',
            'handler:id,name',
        ]);

        $pilgrimUser = $report->pilgrim?->user;
        if (! $pilgrimUser) {
            return;
        }

        $handlerName = $report->handler?->name ?? 'Petugas';

        $this->push->sendToUsers(
            collect([$pilgrimUser]),
            'SOS sedang ditangani',
            "{$handlerName} sedang menangani laporan SOS Anda. Tetap tenang dan tetap di lokasi yang aman.",
            [
                'type' => 'sos_handling',
                'sos_report_id' => $report->id,
                'status' => 'handling',
                'handler_name' => $handlerName,
            ],
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
