<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MobileRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\StaffListRequest;
use App\Http\Resources\Mobile\LocationResource;
use App\Http\Resources\Mobile\PilgrimResource;
use App\Http\Resources\Mobile\SosReportResource;
use App\Services\MobileGroupAccessService;
use Illuminate\Http\Request;

class StaffGroupController extends Controller
{
    public function __construct(private readonly MobileGroupAccessService $access) {}

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
            ->latest('reported_at')
            ->paginate($request->integer('per_page', 30));

        return SosReportResource::collection($reports);
    }
}
