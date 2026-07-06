<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ResolveSosReportRequest;
use App\Models\Branch;
use App\Models\SosReport;
use App\Services\SosResolutionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SosReportController extends Controller
{
    public function __construct(private readonly SosResolutionService $resolution) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SosReport::class);
        $isSuperAdmin = $request->user()->hasRole(UserRole::SuperAdmin->value);
        $branchId = $isSuperAdmin ? $request->integer('branch_id') : $request->user()->branch_id;
        $allowedSorts = ['reported_at', 'status', 'created_at'];
        $sort = in_array($request->string('sort')->toString(), $allowedSorts, true)
            ? $request->string('sort')->toString()
            : 'reported_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $baseQuery = SosReport::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));

        $reports = (clone $baseQuery)
            ->with(['pilgrim:id,registration_number,full_name,phone', 'branch:id,name', 'group:id,name', 'handler:id,name'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn (Builder $query) => $query->whereHas(
                'pilgrim',
                fn (Builder $pilgrim) => $pilgrim
                    ->where('full_name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('registration_number', 'like', '%'.$request->string('search').'%'),
            ))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('monitoring.sos.index', [
            'reports' => $reports,
            'summary' => [
                'active' => (clone $baseQuery)->where('status', 'active')->count(),
                'acknowledged' => (clone $baseQuery)->where('status', 'acknowledged')->count(),
                'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
                'total' => (clone $baseQuery)->count(),
            ],
            'branches' => $isSuperAdmin ? Branch::orderBy('name')->get(['id', 'name']) : collect(),
            'canFilterBranches' => $isSuperAdmin,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(Request $request, SosReport $sosReport): View
    {
        Gate::authorize('view', $sosReport);
        $sosReport->load(['pilgrim', 'branch', 'group.tourLeader', 'group.muthawwif', 'handler']);

        return view('monitoring.sos.show', compact('sosReport'));
    }

    public function resolve(ResolveSosReportRequest $request, SosReport $sosReport): RedirectResponse
    {
        if ($sosReport->status === 'resolved') {
            return back()->with('success', 'Laporan SOS sudah diselesaikan sebelumnya.');
        }

        $this->resolution->resolve(
            $sosReport,
            $request->user(),
            $request->validated('resolution_notes'),
        );

        return redirect()->route('monitoring.sos.show', $sosReport)
            ->with('success', 'Laporan SOS berhasil ditandai selesai.');
    }
}
