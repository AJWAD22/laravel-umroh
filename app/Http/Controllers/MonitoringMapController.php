<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\MonitoringMapRequest;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Services\MonitoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MonitoringMapController extends Controller
{
    public function __construct(private readonly MonitoringService $monitoring) {}

    public function index(Request $request): View
    {
        Gate::authorize('monitoring.view');
        $branchId = $request->user()->hasRole(UserRole::SuperAdmin->value)
            ? null
            : $request->user()->branch_id;

        return view('monitoring.map', [
            'branches' => Branch::query()
                ->when($branchId, fn (Builder $query) => $query->whereKey($branchId))
                ->orderBy('name')->get(['id', 'name']),
            'groups' => Group::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->where('is_active', true)
                ->orderBy('name')->get(['id', 'branch_id', 'departure_id', 'name']),
            'departures' => Departure::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereIn('status', ['scheduled', 'departed'])
                ->orderBy('departure_date')
                ->get(['id', 'branch_id', 'program_name', 'departure_date']),
            'canFilterBranches' => $request->user()->hasRole(UserRole::SuperAdmin->value),
        ]);
    }

    public function data(MonitoringMapRequest $request): JsonResponse
    {
        return response()->json($this->monitoring->markers(
            $request->user(),
            $request->validated(),
        ));
    }
}
