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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GroupMemberController extends Controller
{
    public function __construct(private readonly MobileActivationService $activations) {}

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
        $group->update($request->validated());

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

        return back()->with('success', 'Jamaah berhasil dikeluarkan dari group.');
    }

    public function resetPins(Request $request, Group $group): RedirectResponse
    {
        $this->authorizeGroup($request, $group);

        $result = $this->activations->resetPinsForGroup($request->user(), $group);

        return back()->with('success', "{$result['count']} PIN aktivasi jamaah rombongan berhasil direset.");
    }

    private function authorizeGroup(Request $request, Group $group): void
    {
        Gate::authorize('update', $group);

        if (! $request->user()->hasRole(UserRole::SuperAdmin->value)) {
            abort_unless($group->branch_id === $request->user()->branch_id, 404);
        }
    }
}
