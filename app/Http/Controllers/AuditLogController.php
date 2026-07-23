<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
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
        ]);
    }
}
