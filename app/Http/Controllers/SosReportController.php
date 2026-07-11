<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SosReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SosReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('monitoring.view');
        $branchId = $request->user()->hasRole(UserRole::SuperAdmin->value)
            ? null
            : $request->user()->branch_id;
        $status = $request->query('status');

        $reports = SosReport::query()
            ->with(['pilgrim:id,branch_id,registration_number,full_name,phone', 'group:id,name,code', 'handler:id,name'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when(in_array($status, ['new', 'handling', 'resolved'], true), fn (Builder $query) => $query->where('status', $status))
            ->latest('reported_at')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = SosReport::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));

        return view('monitoring.sos.index', [
            'reports' => $reports,
            'status' => $status,
            'summary' => [
                'new' => (clone $summaryQuery)->where('status', 'new')->count(),
                'handling' => (clone $summaryQuery)->where('status', 'handling')->count(),
                'resolved' => (clone $summaryQuery)->where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function show(Request $request, SosReport $sosReport): View
    {
        Gate::authorize('monitoring.view');
        $this->authorizeBranch($request, $sosReport);

        return view('monitoring.sos.show', [
            'report' => $sosReport->load(['pilgrim.branch', 'pilgrim.latestLocation', 'group', 'handler']),
        ]);
    }

    public function resolve(Request $request, SosReport $sosReport): RedirectResponse
    {
        Gate::authorize('monitoring.view');
        $this->authorizeBranch($request, $sosReport);

        $data = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $sosReport->forceFill([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $data['resolution_notes'] ?? 'Ditandai aman oleh admin.',
        ])->save();

        if (! SosReport::query()->where('pilgrim_id', $sosReport->pilgrim_id)->whereKeyNot($sosReport->id)->active()->exists()) {
            $sosReport->pilgrim()->update(['monitoring_status' => 'normal']);
        }

        return back()->with('success', 'Laporan SOS ditandai sudah aman.');
    }

    private function authorizeBranch(Request $request, SosReport $sosReport): void
    {
        abort_if(
            ! $request->user()->hasRole(UserRole::SuperAdmin->value)
            && $sosReport->branch_id !== $request->user()->branch_id,
            403
        );
    }
}
