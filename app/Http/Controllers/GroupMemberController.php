<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\AssignGroupMembersRequest;
use App\Http\Requests\AssignGroupStaffRequest;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Services\MobileActivationService;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GroupMemberController extends Controller
{
    public function __construct(
        private readonly MobileActivationService $activations,
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request, Group $group): View
    {
        $this->authorizeGroup($request, $group);
        $group->load(['branch', 'departure.hotels', 'tourLeader', 'muthawwif']);

        $members = $group->members()
            ->with('pilgrim')
            ->where('status', 'active')
            ->when($request->filled('member_search'), fn (Builder $query) => $query->whereHas(
                'pilgrim',
                fn (Builder $pilgrimQuery) => $pilgrimQuery
                    ->where('full_name', 'like', '%'.$request->string('member_search').'%')
                    ->orWhere('registration_number', 'like', '%'.$request->string('member_search').'%'),
            ))
            ->latest('joined_at')
            ->paginate(10, ['*'], 'members_page')
            ->withQueryString();

        $availablePilgrims = Pilgrim::query()
            ->where('branch_id', $group->branch_id)
            ->whereIn('status', ['registered', 'active'])
            ->when($request->filled('available_search'), fn (Builder $query) => $query->where(function (Builder $nested) use ($request) {
                $nested->where('full_name', 'like', '%'.$request->string('available_search').'%')
                    ->orWhere('registration_number', 'like', '%'.$request->string('available_search').'%');
            }))
            ->whereDoesntHave('groupMemberships', fn (Builder $query) => $query
                ->where('status', 'active')
                ->where('group_id', '!=', $group->id))
            ->orderBy('full_name')
            ->limit(50)
            ->get();

        $tourLeaders = TourLeader::query()
            ->where('branch_id', $group->branch_id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->pluck('full_name', 'id');
        $muthawwifs = Muthawwif::query()
            ->where('branch_id', $group->branch_id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->pluck('full_name', 'id');

        return view('groups.members', compact(
            'group',
            'members',
            'availablePilgrims',
            'tourLeaders',
            'muthawwifs',
        ));
    }

    public function updateStaff(AssignGroupStaffRequest $request, Group $group): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        $before = $group->only(['tour_leader_id', 'muthawwif_id']);
        $group->update($request->validated());
        $this->audit->record(
            $request->user(),
            'groups.staff.updated',
            $group,
            $before,
            $group->fresh()->only(['tour_leader_id', 'muthawwif_id']),
            ['branch_id' => $group->branch_id],
        );

        return back()->with('success', 'Petugas rombongan berhasil ditentukan.');
    }

    public function store(AssignGroupMembersRequest $request, Group $group): RedirectResponse
    {
        DB::transaction(function () use ($request, $group): void {
            foreach ($request->validated('pilgrim_ids') as $pilgrimId) {
                $group->members()->updateOrCreate(
                    ['pilgrim_id' => $pilgrimId],
                    ['status' => 'active', 'joined_at' => now(), 'left_at' => null],
                );
            }
        });
        $this->audit->record(
            $request->user(),
            'groups.members.assigned',
            $group,
            [],
            ['pilgrim_ids' => $request->validated('pilgrim_ids')],
            ['branch_id' => $group->branch_id],
        );

        return back()->with('success', 'Jamaah berhasil ditambahkan ke group.');
    }

    public function destroy(Request $request, Group $group, GroupMember $member): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        abort_unless($member->group_id === $group->id, 404);

        $member->update([
            'status' => 'removed',
            'left_at' => now(),
        ]);
        $this->audit->record(
            $request->user(),
            'groups.members.removed',
            $group,
            ['member_id' => $member->id, 'pilgrim_id' => $member->pilgrim_id],
            ['status' => 'removed'],
            ['branch_id' => $group->branch_id],
        );

        return back()->with('success', 'Jamaah berhasil dikeluarkan dari group.');
    }

    public function resetPins(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->authorizeGroup($request, $group);

        $result = $this->activations->resetPinsForGroup($request->user(), $group, $data['reason']);

        return back()
            ->with('success', "{$result['count']} PIN aktivasi jamaah rombongan berhasil direset.")
            ->with('reset_pins', $result['pins']);
    }

    private function authorizeGroup(Request $request, Group $group): void
    {
        Gate::authorize('update', $group);

        if (! $request->user()->hasRole(UserRole::SuperAdmin->value)) {
            abort_unless($group->branch_id === $request->user()->branch_id, 404);
        }
    }
}
