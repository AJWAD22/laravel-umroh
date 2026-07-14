<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MobileRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\SendLocationRequest;
use App\Http\Requests\Api\Mobile\StaffListRequest;
use App\Http\Requests\Api\Mobile\StoreStaffCheckpointRequest;
use App\Http\Requests\Api\Mobile\UpdateStaffCheckpointRequest;
use App\Http\Resources\Mobile\CheckpointResource;
use App\Http\Resources\Mobile\HotelResource;
use App\Http\Resources\Mobile\LocationResource;
use App\Http\Resources\Mobile\PilgrimResource;
use App\Http\Resources\Mobile\SosReportResource;
use App\Models\Checkpoint;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\SosReport;
use App\Models\StaffLocation;
use App\Services\AdminNotificationService;
use App\Services\MobileGroupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StaffGroupController extends Controller
{
    public function __construct(
        private readonly MobileGroupAccessService $access,
        private readonly AdminNotificationService $notifications,
    ) {}

    public function leaderPilgrims(StaffListRequest $request)
    {
        return $this->pilgrims($request, MobileRole::TourLeader);
    }

    public function leaderLocations(StaffListRequest $request)
    {
        return $this->locations($request, MobileRole::TourLeader);
    }

    public function muthawwifPilgrims(StaffListRequest $request)
    {
        return $this->pilgrims($request, MobileRole::Muthawwif);
    }

    public function muthawwifLocations(StaffListRequest $request)
    {
        return $this->locations($request, MobileRole::Muthawwif);
    }

    public function leaderHotels(Request $request)
    {
        return $this->hotels($request, MobileRole::TourLeader);
    }

    public function muthawwifHotels(Request $request)
    {
        return $this->hotels($request, MobileRole::Muthawwif);
    }

    public function sendLocation(SendLocationRequest $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->hasRole(MobileRole::TourLeader->value)
            ? MobileRole::TourLeader->value
            : MobileRole::Muthawwif->value;
        $data = $request->validated();
        $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();
        unset($data['recorded_at']);

        $location = StaffLocation::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'branch_id' => $user->branch_id,
                'role' => $role,
                ...$data,
                'recorded_at' => $recordedAt,
            ],
        );

        return response()->json([
            'message' => 'Lokasi petugas berhasil disimpan.',
            'location' => new LocationResource($location),
        ], 201);
    }

    public function storeCheckpoint(StoreStaffCheckpointRequest $request)
    {
        $user = $request->user();
        $role = $user->hasRole(MobileRole::TourLeader->value)
            ? MobileRole::TourLeader
            : MobileRole::Muthawwif;
        $groupIds = $this->access->groupIdsForStaff($user, $role);
        abort_if($groupIds->isEmpty(), 422, 'Petugas belum ditugaskan ke rombongan aktif.');

        $groupId = $request->integer('group_id') ?: (int) $groupIds->first();
        abort_unless($groupIds->contains($groupId), 403, 'Rombongan tidak dapat diakses.');

        $group = Group::query()->findOrFail($groupId);
        $data = $request->validated();

        $checkpoint = Checkpoint::query()->create([
            'branch_id' => $user->branch_id,
            'departure_id' => $group->departure_id,
            'group_id' => $group->id,
            'name' => $data['name'],
            'category' => 'titik_kumpul',
            'city' => $data['city'] ?? 'other',
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'description' => $data['description'] ?? 'Dibuat oleh petugas melalui aplikasi.',
            'is_active' => true,
        ]);

        return (new CheckpointResource($checkpoint->load(['branch', 'departure', 'group'])))
            ->additional(['message' => 'Titik kumpul berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    public function updateCheckpoint(UpdateStaffCheckpointRequest $request, Checkpoint $checkpoint)
    {
        $this->authorizeStaffCheckpoint($request, $checkpoint);
        $checkpoint->fill($request->validated())->save();

        return (new CheckpointResource($checkpoint->load(['branch', 'departure', 'group'])))
            ->additional(['message' => 'Titik kumpul berhasil diperbarui.']);
    }

    public function deactivateCheckpoint(Request $request, Checkpoint $checkpoint)
    {
        $this->authorizeStaffCheckpoint($request, $checkpoint);
        $checkpoint->forceFill(['is_active' => false])->save();

        return (new CheckpointResource($checkpoint->load(['branch', 'departure', 'group'])))
            ->additional(['message' => 'Titik kumpul berhasil dinonaktifkan.']);
    }

    public function sosReports(Request $request)
    {
        $role = $this->mobileRole($request);
        $status = $request->query('status');
        $reports = $this->access->sosReportsForStaff($request->user(), $role)
            ->when(
                in_array($status, ['new', 'handling', 'resolved'], true),
                fn ($query) => $query->where('status', $status)
            )
            ->latest('reported_at')
            ->paginate($request->integer('per_page', 30));

        return SosReportResource::collection($reports);
    }

    public function acknowledge(Request $request, SosReport $sosReport): SosReportResource
    {
        $this->authorizeSos($request, $sosReport);
        $shouldNotifyPilgrim = $sosReport->status === 'new';

        if ($shouldNotifyPilgrim) {
            $sosReport->forceFill([
                'status' => 'handling',
                'handled_by' => $request->user()->id,
                'acknowledged_at' => now(),
            ])->save();

            $this->notifications->sosAcknowledged(
                $sosReport->fresh(['pilgrim.user', 'handler']),
            );
        }

        return new SosReportResource($sosReport->fresh(['pilgrim.branch', 'pilgrim.latestLocation', 'group', 'handler']));
    }

    public function resolve(Request $request, SosReport $sosReport): SosReportResource
    {
        $this->authorizeSos($request, $sosReport);
        $data = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $sosReport->forceFill([
            'status' => 'resolved',
            'handled_by' => $sosReport->handled_by ?: $request->user()->id,
            'acknowledged_at' => $sosReport->acknowledged_at ?: now(),
            'resolved_at' => now(),
            'resolution_notes' => $data['resolution_notes'] ?? 'Sudah ditangani oleh petugas.',
        ])->save();

        $hasActive = SosReport::query()
            ->where('pilgrim_id', $sosReport->pilgrim_id)
            ->whereKeyNot($sosReport->id)
            ->active()
            ->exists();

        if (! $hasActive) {
            $sosReport->pilgrim()->update(['monitoring_status' => 'normal']);
        }

        return new SosReportResource($sosReport->fresh(['pilgrim.branch', 'pilgrim.latestLocation', 'group', 'handler']));
    }

    private function pilgrims(Request $request, MobileRole $role)
    {
        $pilgrims = $this->access->pilgrimsForStaff($request->user(), $role)
            ->with(['branch:id,name', 'latestLocation'])
            ->orderBy('full_name')
            ->paginate($request->integer('per_page', 30));

        return PilgrimResource::collection($pilgrims);
    }

    private function locations(Request $request, MobileRole $role)
    {
        $locations = $this->access->pilgrimsForStaff($request->user(), $role)
            ->whereHas('latestLocation')
            ->with('latestLocation')
            ->get()
            ->map(fn ($pilgrim) => [
                'pilgrim' => (new PilgrimResource($pilgrim))->resolve($request),
                'location' => (new LocationResource($pilgrim->latestLocation))->resolve($request),
            ]);

        return response()->json(['data' => $locations]);
    }

    private function mobileRole(Request $request): MobileRole
    {
        return $request->user()->hasRole(MobileRole::TourLeader->value)
            ? MobileRole::TourLeader
            : MobileRole::Muthawwif;
    }

    private function authorizeSos(Request $request, SosReport $sosReport): void
    {
        $role = $this->mobileRole($request);
        abort_unless(
            $this->access->groupIdsForStaff($request->user(), $role)->contains($sosReport->group_id),
            403,
            'Laporan SOS tidak dapat diakses.'
        );
    }

    private function authorizeStaffCheckpoint(Request $request, Checkpoint $checkpoint): void
    {
        $user = $request->user();
        $role = $user->hasRole(MobileRole::TourLeader->value)
            ? MobileRole::TourLeader
            : MobileRole::Muthawwif;
        $groupIds = $this->access->groupIdsForStaff($user, $role);

        abort_unless(
            (int) $checkpoint->branch_id === (int) $user->branch_id
                && $checkpoint->category === 'titik_kumpul'
                && $checkpoint->group_id !== null
                && $groupIds->contains((int) $checkpoint->group_id),
            403,
            'Titik kumpul ini tidak dapat diubah oleh Anda.',
        );
    }

    private function hotels(Request $request, MobileRole $role)
    {
        $groupIds = $this->access->groupIdsForStaff($request->user(), $role);
        $hotels = Hotel::query()
            ->whereHas('departures.groups', fn ($query) => $query
                ->whereIn('groups.id', $groupIds)
                ->where('groups.is_active', true))
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        return HotelResource::collection($hotels);
    }
}
