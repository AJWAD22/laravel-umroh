<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\SystemSettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly AuditLogService $audit,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('audit.global.view') || $user->can('audit.branch.view'), 403);

        $branchId = $user->hasRole(UserRole::SuperAdmin->value)
            ? $request->integer('branch_id') ?: null
            : $user->branch_id;

        $logs = AuditLog::query()
            ->with(['branch:id,name', 'actor:id,name,email'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when($request->filled('action'), fn (Builder $query) => $query
                ->where('action', 'like', '%'.$request->string('action')->toString().'%'))
            ->when($request->filled('actor'), fn (Builder $query) => $query
                ->whereHas('actor', fn (Builder $actorQuery) => $actorQuery
                    ->where('name', 'like', '%'.$request->string('actor')->toString().'%')
                    ->orWhere('email', 'like', '%'.$request->string('actor')->toString().'%')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'canFilterBranches' => $user->hasRole(UserRole::SuperAdmin->value),
            'retentionDays' => (int) $this->settings->get('audit_log_retention_days', 365),
            'canPurgeExpired' => $user->can('system-settings.manage'),
        ]);
    }

    public function purgeExpired(Request $request): RedirectResponse
    {
        Gate::authorize('system-settings.manage');

        $retentionDays = max(30, (int) $this->settings->get('audit_log_retention_days', 365));
        $cutoff = now()->subDays($retentionDays);
        $deleted = AuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->audit->record(
            $request->user(),
            'audit.retention.purged',
            metadata: [
                'retention_days' => $retentionDays,
                'cutoff' => $cutoff->toDateTimeString(),
                'deleted_count' => $deleted,
            ],
        );

        return back()->with('success', "Audit log kedaluwarsa berhasil dibersihkan: {$deleted} data.");
    }
}
