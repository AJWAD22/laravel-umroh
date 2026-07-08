<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MobileRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\SendLocationRequest;
use App\Http\Requests\Api\Mobile\StaffListRequest;
use App\Http\Resources\Mobile\HotelResource;
use App\Http\Resources\Mobile\LocationResource;
use App\Http\Resources\Mobile\PilgrimResource;
use App\Http\Resources\Mobile\SosReportResource;
use App\Models\Hotel;
use App\Models\StaffLocation;
use App\Models\SosReport;
use App\Services\MobileGroupAccessService;
use App\Services\SosResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StaffGroupController extends Controller
{
    public function __construct(
        private readonly MobileGroupAccessService $access,
        private readonly SosResolutionService $resolution,
    ) {}

    public function leaderPilgrims(StaffListRequest $request)
    {
        return $this->pilgrims($request, MobileRole::TourLeader);
    }

    public function leaderLocations(StaffListRequest $request)
    {
        return $this->locations($request, MobileRole::TourLeader);
    }

    public function leaderSos(StaffListRequest $request)
    {
        return $this->sos($request, MobileRole::TourLeader);
    }

    public function muthawwifPilgrims(StaffListRequest $request)
    {
        return $this->pilgrims($request, MobileRole::Muthawwif);
    }

    public function muthawwifLocations(StaffListRequest $request)
    {
        return $this->locations($request, MobileRole::Muthawwif);
    }

    public function muthawwifSos(StaffListRequest $request)
    {
        return $this->sos($request, MobileRole::Muthawwif);
    }

    public function leaderHotels(Request $request)
    {
        return $this->hotels($request, MobileRole::TourLeader);
    }

    public function muthawwifHotels(Request $request)
    {
        return $this->hotels($request, MobileRole::Muthawwif);
    }

    public function leaderResolveSos(Request $request, SosReport $sosReport): JsonResponse
    {
        return $this->resolveSos($request, $sosReport, MobileRole::TourLeader);
    }

    public function muthawwifResolveSos(Request $request, SosReport $sosReport): JsonResponse
    {
        return $this->resolveSos($request, $sosReport, MobileRole::Muthawwif);
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

    private function sos(Request $request, MobileRole $role)
    {
        $reports = $this->access->sosForStaff($request->user(), $role)
            ->with('pilgrim.branch')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when(! $request->filled('status'), fn ($query) => $query->whereIn('status', ['active', 'acknowledged']))
            ->latest('reported_at')
            ->paginate($request->integer('per_page', 30));

        return SosReportResource::collection($reports);
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

    private function resolveSos(
        Request $request,
        SosReport $sosReport,
        MobileRole $role,
    ): JsonResponse {
        abort_unless(
            $this->access->sosForStaff($request->user(), $role)
                ->whereKey($sosReport->id)
                ->exists(),
            404,
        );
        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $resolved = $this->resolution->resolve(
            $sosReport,
            $request->user(),
            $validated['resolution_notes'] ?? 'Jamaah telah diamankan oleh petugas.',
        );

        return response()->json([
            'message' => 'Laporan SOS selesai. Jamaah telah ditandai aman.',
            'data' => new SosReportResource($resolved->load('pilgrim.branch')),
        ]);
    }
}
