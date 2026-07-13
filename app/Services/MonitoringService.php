<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\PilgrimLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MonitoringService
{
    public function __construct(private readonly SystemSettingService $settings) {}

    /**
     * @param  array{branch_id?: int|null, group_id?: int|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function markers(User $user, array $filters): array
    {
        $branchId = $user->hasRole(UserRole::SuperAdmin->value)
            ? ($filters['branch_id'] ?? null)
            : $user->branch_id;
        $groupId = $filters['group_id'] ?? null;
        $offlineThreshold = now()->subMinutes(
            (int) $this->settings->get('gps_offline_threshold_minutes', 10)
        );

        $pilgrims = PilgrimLocation::query()
            ->with([
                'pilgrim:id,branch_id,registration_number,full_name,phone,photo_path,monitoring_status',
                'pilgrim.branch:id,name',
                'group:id,name,tour_leader_id,muthawwif_id',
                'group.tourLeader:id,full_name',
                'group.muthawwif:id,full_name',
            ])
            ->whereHas('pilgrim', fn (Builder $query) => $query
                ->when($branchId, fn (Builder $branchQuery) => $branchQuery->where('branch_id', $branchId)))
            ->when($groupId, fn (Builder $query) => $query->where('group_id', $groupId))
            ->get()
            ->map(function (PilgrimLocation $location) use ($offlineThreshold): array {
                $status = $location->pilgrim->monitoring_status === 'sos'
                    ? 'sos'
                    : ($location->gps_status === 'online' && $location->recorded_at->gte($offlineThreshold)
                        ? 'online'
                        : 'offline');

                return [
                    'id' => "pilgrim-{$location->pilgrim_id}",
                    'type' => 'pilgrim',
                    'name' => $location->pilgrim->full_name,
                    'registration_number' => $location->pilgrim->registration_number,
                    'photo_url' => $location->pilgrim->photo_path
                        ? asset('storage/'.$location->pilgrim->photo_path)
                        : null,
                    'phone' => $location->pilgrim->phone,
                    'branch_id' => $location->pilgrim->branch_id,
                    'branch' => $location->pilgrim->branch->name,
                    'group_id' => $location->group_id,
                    'group' => $location->group?->name,
                    'tour_leader' => $location->group?->tourLeader?->full_name,
                    'muthawwif' => $location->group?->muthawwif?->full_name,
                    'status' => $status,
                    'battery' => $location->battery_level,
                    'accuracy' => $location->accuracy !== null ? (float) $location->accuracy : null,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'location_name' => 'Koordinat GPS terakhir',
                    'updated_at' => $location->recorded_at->toIso8601String(),
                ];
            })
            ->when(
                $filters['status'] ?? null,
                fn ($markers, $status) => $markers->where('status', $status),
            )
            ->values();

        return [
            'markers' => $pilgrims->values(),
            'summary' => [
                'total' => $pilgrims->count(),
                'online' => $pilgrims->where('status', 'online')->count(),
                'offline' => $pilgrims->where('status', 'offline')->count(),
                'sos' => $pilgrims->where('status', 'sos')->count(),
            ],
            'generated_at' => now()->toIso8601String(),
            'source' => 'database',
        ];
    }
}
