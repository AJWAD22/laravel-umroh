<?php

namespace App\Http\Controllers;

use App\Models\Departure;
use App\Models\PilgrimRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicRegistrationController extends Controller
{
    private const SESSION_KEY = 'public_registration_biodata';

    public function create(Request $request): View
    {
        return view('public.registration-biodata', [
            'biodata' => $request->session()->get(self::SESSION_KEY, []),
        ]);
    }

    public function storeBiodata(Request $request): RedirectResponse
    {
        $biodata = $request->validate($this->biodataRules());
        $request->session()->put(self::SESSION_KEY, $biodata);

        return redirect()->route('public-registration.packages');
    }

    public function packages(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('public-registration.create')
                ->with('error', 'Isi biodata terlebih dahulu sebelum memilih paket.');
        }

        return view('public.registration-packages', [
            'biodata' => $request->session()->get(self::SESSION_KEY),
            'packages' => $this->availablePackages()->get(),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $biodata = $request->session()->get(self::SESSION_KEY);
        if (! is_array($biodata)) {
            return redirect()->route('public-registration.create')
                ->with('error', 'Sesi biodata berakhir. Silakan isi kembali biodata jamaah.');
        }

        $selection = $request->validate([
            'departure_id' => ['required', 'integer', 'exists:departures,id'],
        ]);
        $departure = $this->availablePackages()
            ->whereKey($selection['departure_id'])
            ->first();

        if (! $departure) {
            throw ValidationException::withMessages([
                'departure_id' => ['Paket tidak tersedia. Silakan pilih paket lain.'],
            ]);
        }

        DB::transaction(function () use ($biodata, $departure): void {
            $departure = Departure::query()->lockForUpdate()->findOrFail($departure->id);

            if (! $departure->is_public
                || $departure->status !== 'scheduled'
                || $departure->departure_date->isBefore(today())) {
                throw ValidationException::withMessages([
                    'departure_id' => ['Paket tidak lagi tersedia untuk registrasi.'],
                ]);
            }

            $alreadyRegistered = PilgrimRegistration::query()
                ->where('departure_id', $departure->id)
                ->where('status', '!=', 'cancelled')
                ->where(function (Builder $query) use ($biodata): void {
                    $query->where('phone', $biodata['phone']);
                    if (! empty($biodata['nik'])) {
                        $query->orWhere('nik_hash', PilgrimRegistration::identityDigest($biodata['nik']));
                    }
                })
                ->exists();

            if ($alreadyRegistered) {
                throw ValidationException::withMessages([
                    'departure_id' => ['Biodata ini sudah terdaftar pada paket yang dipilih.'],
                ]);
            }

            $activeRegistrations = PilgrimRegistration::query()
                ->where('departure_id', $departure->id)
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($departure->quota !== null && $activeRegistrations >= $departure->quota) {
                throw ValidationException::withMessages([
                    'departure_id' => ['Kuota paket sudah penuh. Silakan pilih paket lain.'],
                ]);
            }

            PilgrimRegistration::query()->create([
                ...$biodata,
                'departure_id' => $departure->id,
                'branch_id' => $departure->branch_id,
                'status' => 'submitted',
            ]);
        });

        $request->session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('packages.show', $departure)
            ->with('success', 'Registrasi berhasil dikirim. Admin cabang akan menghubungi jamaah untuk konfirmasi keberangkatan.');
    }

    private function availablePackages(): Builder
    {
        return Departure::query()
            ->with(['branch', 'hotels', 'itineraries'])
            ->withCount([
                'registrations as active_registrations_count' => fn (Builder $query) => $query
                    ->whereNotIn('status', ['cancelled']),
            ])
            ->where('is_public', true)
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', today())
            ->orderBy('departure_date');
    }

    /** @return array<string, array<int, mixed>> */
    private function biodataRules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:20'],
            'passport_number' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
