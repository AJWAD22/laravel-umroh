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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->with(['pilgrim.user.mobileDevices' => fn ($query) => $query->latest('last_used_at')])
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

    public function generateMissingPins(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->authorizeGroup($request, $group);
        $result = $this->activations->generateMissingPinsForGroup($request->user(), $group, $data['reason']);

        return back()
            ->with('success', "{$result['count']} PIN aktivasi jamaah berhasil dibuat.")
            ->with('reset_pins', $result['pins']);
    }

    public function resetPilgrimPin(Request $request, Group $group, Pilgrim $pilgrim): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->authorizeGroup($request, $group);
        $this->authorizeGroupPilgrim($group, $pilgrim);
        $pin = $this->activations->generatePin($request->user(), $pilgrim, $data['reason']);

        return back()
            ->with('success', "PIN aktivasi {$pilgrim->full_name} berhasil dibuat.")
            ->with('reset_pins', [[
                'pilgrim_id' => $pilgrim->id,
                'registration_number' => $pilgrim->registration_number,
                'name' => $pilgrim->full_name,
                'pin' => $pin,
            ]]);
    }

    public function revokePilgrimDevices(Request $request, Group $group, Pilgrim $pilgrim): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $this->authorizeGroup($request, $group);
        $this->authorizeGroupPilgrim($group, $pilgrim);
        $count = $this->activations->revokePilgrimDevices($request->user(), $pilgrim, $data['reason']);

        return back()->with('success', "{$count} perangkat {$pilgrim->full_name} berhasil dicabut.");
    }

    public function activationList(Request $request, Group $group): StreamedResponse
    {
        $this->authorizeGroup($request, $group);
        $group->load(['pilgrims.user.mobileDevices']);
        $fileName = str($group->code ?: $group->name)->slug('-').'-aktivasi.csv';

        return response()->streamDownload(function () use ($group): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Jamaah', 'No Registrasi', 'PIN', 'Perangkat', 'Terakhir Aktif']);
            foreach ($group->pilgrims()->wherePivot('status', 'active')->with('user.mobileDevices')->orderBy('full_name')->get() as $pilgrim) {
                $activeDevice = $pilgrim->user?->mobileDevices
                    ->whereNull('revoked_at')
                    ->sortByDesc('last_used_at')
                    ->first();
                fputcsv($output, [
                    $pilgrim->full_name,
                    $pilgrim->registration_number,
                    $pilgrim->activation_pin_generated_at ? 'Sudah dibuat' : 'Belum dibuat',
                    $activeDevice ? 'Aktif' : 'Belum aktif',
                    $activeDevice?->last_used_at?->toDateTimeString() ?: '',
                ]);
            }
            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function authorizeGroup(Request $request, Group $group): void
    {
        Gate::authorize('update', $group);

        if (! $request->user()->hasRole(UserRole::SuperAdmin->value)) {
            abort_unless($group->branch_id === $request->user()->branch_id, 404);
        }
    }

    private function authorizeGroupPilgrim(Group $group, Pilgrim $pilgrim): void
    {
        abort_unless((int) $pilgrim->branch_id === (int) $group->branch_id, 404);
        abort_unless($group->members()
            ->where('pilgrim_id', $pilgrim->id)
            ->where('status', 'active')
            ->exists(), 404);
    }
}
