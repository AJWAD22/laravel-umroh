<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Departure;
use App\Models\Group;
use App\Models\PilgrimRegistration;
use App\Services\RegistrationApprovalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationManagementController extends Controller
{
    public function __construct(
        private readonly RegistrationApprovalService $approvals,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(
            $user->can('registrations.view') || $user->can('registrations.manage'),
            403,
        );
        $branchId = $user->hasRole(UserRole::SuperAdmin->value) ? null : $user->branch_id;

        $registrations = PilgrimRegistration::query()
            ->with([
                'branch:id,name',
                'departure:id,branch_id,program_name,departure_date',
                'user.pilgrim.groupMemberships.group:id,name',
            ])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when(
                $user->hasRole(UserRole::SuperAdmin->value) && $request->filled('branch_id'),
                fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')),
            )
            ->when($request->filled('departure_id'), fn (Builder $query) => $query
                ->where('departure_id', $request->integer('departure_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query
                ->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn (Builder $query) => $query
                ->where('payment_status', $request->string('payment_status')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('registrations.index', [
            'registrations' => $registrations,
            'departures' => Departure::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->latest('departure_date')
                ->pluck('program_name', 'id'),
            'groups' => Group::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->where('is_active', true)
                ->whereNotNull('departure_id')
                ->orderBy('name')
                ->get(['id', 'departure_id', 'name', 'capacity']),
            'canManage' => $user->can('registrations.manage'),
        ]);
    }

    public function update(Request $request, PilgrimRegistration $registration): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('registrations.manage'), 403);
        abort_unless((int) $registration->branch_id === (int) $user->branch_id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'submitted', 'revision_requested', 'approved', 'in_group', 'rejected', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'pending_branch_payment', 'down_payment', 'paid', 'verified', 'cancelled'])],
            'group_id' => ['nullable', 'integer'],
            'revision_notes' => ['nullable', 'string', 'max:1500'],
        ]);
        if ($data['status'] === 'in_group' && ! in_array($data['payment_status'], ['paid', 'verified'], true)) {
            throw ValidationException::withMessages([
                'payment_status' => ['Jamaah hanya bisa masuk rombongan setelah pembayaran lunas.'],
            ]);
        }

        $result = $this->approvals->update($user, $registration, $data);
        $message = 'Status registrasi jamaah berhasil diperbarui.';
        if ($result['pilgrim']) {
            $message = 'Registrasi disetujui dan jamaah sudah masuk ke operasional perjalanan.';
        }
        if ($result['pin']) {
            $message .= " PIN aktivasi: {$result['pin']}";
        }

        return back()->with('success', $message);
    }
}
