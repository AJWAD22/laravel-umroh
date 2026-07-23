<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Checkpoint;
use App\Models\Group;
use App\Models\PilgrimLocation;
use App\Models\StaffLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MonitoringService
{
    public function __construct(private readonly SystemSettingService $settings) {}

    /**
     * Menghasilkan marker peta dan ringkasan status jamaah.
     * Status online/offline dihitung dari waktu lokasi terakhir, bukan hanya
     * dari status yang dikirim perangkat.
     *
     * @param  array{branch_id?: int|null, departure_id?: int|null, group_id?: int|null, status?: string|null, search?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function markers(User $user, array $filters): array
    {
        $branchId = $user->hasRole(UserRole::SuperAdmin->value)
            ? ($filters['branch_id'] ?? null)
            : $user->branch_id;
        $groupId = $filters['group_id'] ?? null;
        $departureId = $filters['departure_id'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        // Ketika rombongan dipilih, jadwalnya menjadi sumber kebenaran. Dengan
        // begitu titik tingkat jadwal yang tampil di web sama dengan aplikasi.
        if ($groupId) {
            $selectedGroup = Group::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->find($groupId, ['id', 'departure_id']);

            if ($selectedGroup) {
                $departureId = $selectedGroup->departure_id;
            }
        }

        // Live Map dan Dashboard harus memakai batas waktu yang sama.
        // Default dari seeder: 10 menit. Jika lokasi terakhir lebih lama dari ini,
        // Jamaah dianggap offline walaupun kolom gps_status masih "online".
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
                ->when($branchId, fn (Builder $branchQuery) => $branchQuery->where('branch_id', $branchId))
                ->when($search, fn (Builder $searchQuery) => $searchQuery->where(function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                }))
                ->whereHas('user.mobileDevices', fn (Builder $deviceQuery) => $deviceQuery->whereNull('revoked_at')))
            ->when($branchId, fn (Builder $query) => $query->where(function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            }))
            ->when($departureId, fn (Builder $query) => $query->whereHas(
                'group',
                fn (Builder $groupQuery) => $groupQuery->where('departure_id', $departureId),
            ))
            ->when($groupId, fn (Builder $query) => $query->where('group_id', $groupId))
            ->get()
            ->map(function (PilgrimLocation $location) use ($offlineThreshold): array {
                // Prioritas status:
                // 1. SOS selalu ditandai merah.
                // 2. Online hanya jika GPS masih online dan waktu lokasinya masih baru.
                // 3. Selain itu dianggap offline.
                $status = $location->pilgrim->monitoring_status === 'sos'
                    ? 'sos'
                    : ($location->gps_status === 'online' && $location->recorded_at->gte($offlineThreshold)
                        ? 'online'
                        : 'offline');

                return [
                    'id' => "pilgrim-{$location->pilgrim_id}",
                    'pilgrim_id' => $location->pilgrim_id,
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

        $scopeGroups = Group::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when($departureId, fn (Builder $query) => $query->where('departure_id', $departureId))
            ->when($groupId, fn (Builder $query) => $query->whereKey($groupId))
            ->where('is_active', true)
            ->get(['id', 'branch_id', 'departure_id', 'name', 'tour_leader_id', 'muthawwif_id']);

        $tourLeaderIds = $scopeGroups->pluck('tour_leader_id')->filter()->unique();
        $muthawwifIds = $scopeGroups->pluck('muthawwif_id')->filter()->unique();

        $staff = StaffLocation::query()
            ->with(['user:id,branch_id,name,phone_number,photo_path', 'user.branch:id,name', 'user.tourLeader:id,user_id', 'user.muthawwif:id,user_id'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereHas('user', fn (Builder $query) => $query->where(function (Builder $query) use ($tourLeaderIds, $muthawwifIds): void {
                $query->whereHas('tourLeader', fn (Builder $staffQuery) => $staffQuery->whereIn('id', $tourLeaderIds))
                    ->orWhereHas('muthawwif', fn (Builder $staffQuery) => $staffQuery->whereIn('id', $muthawwifIds));
            }))
            ->get()
            ->map(function (StaffLocation $location) use ($offlineThreshold, $scopeGroups): array {
                $isTourLeader = $location->user->tourLeader !== null;
                $staffId = $isTourLeader ? $location->user->tourLeader->id : $location->user->muthawwif?->id;
                $groups = $scopeGroups->filter(fn (Group $group) => $isTourLeader
                    ? (int) $group->tour_leader_id === (int) $staffId
                    : (int) $group->muthawwif_id === (int) $staffId);

                return [
                    'id' => "staff-{$location->user_id}",
                    'type' => $isTourLeader ? 'tour-leader' : 'muthawwif',
                    'name' => $location->user->name,
                    'phone' => $location->user->phone_number,
                    'photo_url' => $location->user->photo_path ? asset('storage/'.$location->user->photo_path) : null,
                    'branch_id' => $location->branch_id,
                    'branch' => $location->user->branch?->name,
                    'group' => $groups->pluck('name')->join(', '),
                    'status' => $location->recorded_at->gte($offlineThreshold) ? 'online' : 'offline',
                    'battery' => $location->battery_level,
                    'accuracy' => $location->accuracy !== null ? (float) $location->accuracy : null,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'updated_at' => $location->recorded_at->toIso8601String(),
                ];
            })->values();

        $checkpoints = Checkpoint::query()
            ->with(['branch:id,name', 'departure:id,program_name', 'group:id,name'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->where('is_active', true)
            ->where(function (Builder $query) use ($departureId, $groupId): void {
                $query->where(fn (Builder $query) => $query->whereNull('departure_id')->whereNull('group_id'));

                if ($departureId) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->where('departure_id', $departureId)
                        ->whereNull('group_id'));
                }

                if ($groupId) {
                    $query->orWhere('group_id', $groupId);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Checkpoint $checkpoint): array => [
                'id' => "checkpoint-{$checkpoint->id}",
                'type' => 'checkpoint',
                'name' => $checkpoint->name,
                'category' => $checkpoint->category,
                'city' => $checkpoint->city,
                'address' => $checkpoint->address,
                'description' => $checkpoint->description,
                'branch_id' => $checkpoint->branch_id,
                'branch' => $checkpoint->branch?->name,
                'departure' => $checkpoint->departure?->program_name,
                'group' => $checkpoint->group?->name,
                'radius' => $checkpoint->geofence_radius_meters,
                'latitude' => (float) $checkpoint->latitude,
                'longitude' => (float) $checkpoint->longitude,
            ])->values();

        return [
            'markers' => $pilgrims->values(),
            'staff' => $staff,
            'checkpoints' => $checkpoints,
            'summary' => [
                'total' => $pilgrims->count(),
                'online' => $pilgrims->where('status', 'online')->count(),
                'offline' => $pilgrims->where('status', 'offline')->count(),
                'sos' => $pilgrims->where('status', 'sos')->count(),
                'staff' => $staff->count(),
                'checkpoints' => $checkpoints->count(),
            ],
            'generated_at' => now()->toIso8601String(),
            'source' => 'database',
        ];
    }
}
