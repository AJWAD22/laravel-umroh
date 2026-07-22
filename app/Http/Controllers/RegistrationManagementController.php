<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Departure;
use App\Models\PilgrimRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationManagementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $branchId = $user->hasRole(UserRole::SuperAdmin->value) ? null : $user->branch_id;

        $registrations = PilgrimRegistration::query()
            ->with(['branch:id,name', 'departure:id,branch_id,program_name,departure_date'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when(
                $user->hasRole(UserRole::SuperAdmin->value) && $request->filled('branch_id'),
                fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')),
            )
            ->when($request->filled('departure_id'), fn (Builder $query) => $query
                ->where('departure_id', $request->integer('departure_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query
                ->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%"));
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
            'canManage' => $user->hasRole(UserRole::BranchAdmin->value),
        ]);
    }

    public function update(Request $request, PilgrimRegistration $registration): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(UserRole::BranchAdmin->value), 403);
        abort_unless((int) $registration->branch_id === (int) $user->branch_id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['submitted', 'contacted', 'approved', 'cancelled'])],
        ]);

        $registration->update($data);

        return back()->with('success', 'Status registrasi jamaah berhasil diperbarui.');
    }
}
