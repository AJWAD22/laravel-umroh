<?php

namespace App\Http\Controllers;

use App\Enums\MobileRole;
use App\Models\Departure;
use App\Models\PilgrimPortalAccount;
use App\Models\PilgrimRegistration;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class PilgrimPortalController extends Controller
{
    private const SELECTED_PACKAGE = 'pilgrim_portal_selected_package';

    public function register(): View
    {
        return view('portal.auth.register');
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('pilgrim_portal_accounts', 'email'),
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);
        $phone = $this->normalizePhone($data['phone']);

        if (strlen($phone) < 10 || PilgrimPortalAccount::query()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Nomor WhatsApp tidak valid atau sudah digunakan. Silakan masuk jika sudah memiliki akun.'],
            ]);
        }

        $user = DB::transaction(function () use ($data, $phone): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => 'jamaah.'.Str::uuid().'@internal.mantauumroh.local',
                'phone_number' => $phone,
                'password' => $data['password'],
                'is_active' => true,
            ]);
            PilgrimPortalAccount::query()->create([
                'user_id' => $user->id,
                'phone' => $phone,
                'email' => $data['email'] ?: null,
            ]);
            Role::findOrCreate(MobileRole::Pilgrim->value, 'web');
            $user->assignRole(MobileRole::Pilgrim->value);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.packages.index')
            ->with('success', 'Akun berhasil dibuat. Silakan pilih paket perjalanan yang sesuai.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    public function dashboard(Request $request): View
    {
        return view('portal.dashboard', [
            'account' => $request->user()->portalAccount,
            'registrations' => $request->user()->portalRegistrations()
                ->with(['branch', 'departure.hotels'])
                ->latest()
                ->get(),
        ]);
    }

    public function packages(): View
    {
        return view('portal.packages.index', [
            'packages' => $this->availablePackages()->get(),
        ]);
    }

    public function showPackage(Departure $departure): View
    {
        abort_unless($this->availablePackages()->whereKey($departure->id)->exists(), 404);

        return view('portal.packages.show', [
            'package' => $departure->load(['branch', 'hotels', 'itineraries']),
        ]);
    }

    public function selectPackage(Request $request, Departure $departure): RedirectResponse
    {
        abort_unless($this->availablePackages()->whereKey($departure->id)->exists(), 404);

        if ($request->user()->portalRegistrations()
            ->where('departure_id', $departure->id)
            ->where('status', '!=', 'cancelled')
            ->exists()) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'Anda sudah mendaftar pada paket tersebut.');
        }

        $request->session()->put(self::SELECTED_PACKAGE, $departure->id);

        return redirect()->route('portal.biodata.edit');
    }

    public function biodata(Request $request): View|RedirectResponse
    {
        $departure = $this->selectedPackage($request);
        if (! $departure) {
            return redirect()->route('portal.packages.index')
                ->with('error', 'Pilih paket perjalanan sebelum mengisi biodata.');
        }

        return view('portal.biodata', [
            'package' => $departure,
            'account' => $request->user()->portalAccount,
        ]);
    }

    public function submitBiodata(Request $request): RedirectResponse
    {
        $departure = $this->selectedPackage($request);
        if (! $departure) {
            return redirect()->route('portal.packages.index')
                ->with('error', 'Sesi paket berakhir. Silakan pilih paket kembali.');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits_between:12,20'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'passport_number' => ['nullable', 'string', 'max:30'],
            'passport_expired_at' => ['nullable', 'date', 'after:today'],
            'address' => ['required', 'string', 'max:1500'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'confirmation' => ['accepted'],
        ]);
        unset($data['confirmation']);

        DB::transaction(function () use ($request, $departure, $data): void {
            $lockedDeparture = Departure::query()->lockForUpdate()->findOrFail($departure->id);
            $activeRegistrations = PilgrimRegistration::query()
                ->where('departure_id', $lockedDeparture->id)
                ->where('status', '!=', 'cancelled')
                ->count();

            if (! $lockedDeparture->is_public
                || $lockedDeparture->status !== 'scheduled'
                || $lockedDeparture->departure_date->isBefore(today())
                || ($lockedDeparture->quota !== null && $activeRegistrations >= $lockedDeparture->quota)) {
                throw ValidationException::withMessages([
                    'confirmation' => ['Paket tidak lagi tersedia atau kuotanya sudah penuh.'],
                ]);
            }

            if (PilgrimRegistration::query()
                ->where('departure_id', $lockedDeparture->id)
                ->where('status', '!=', 'cancelled')
                ->where(fn (Builder $query) => $query
                    ->where('user_id', $request->user()->id)
                    ->orWhere('nik', $data['nik']))
                ->exists()) {
                throw ValidationException::withMessages([
                    'confirmation' => ['Akun atau NIK ini sudah terdaftar pada paket yang dipilih.'],
                ]);
            }

            PilgrimRegistration::query()->create([
                ...$data,
                'user_id' => $request->user()->id,
                'branch_id' => $lockedDeparture->branch_id,
                'departure_id' => $lockedDeparture->id,
                'phone' => $request->user()->portalAccount->phone,
                'status' => 'submitted',
                'payment_status' => 'pending_branch_payment',
                'submitted_at' => now(),
            ]);
            $request->user()->forceFill(['name' => $data['full_name']])->save();
        });

        $request->session()->forget(self::SELECTED_PACKAGE);

        return redirect()->route('portal.dashboard')
            ->with('success', 'Pendaftaran berhasil dikirim. Pembayaran dilakukan langsung di kantor cabang yang tertera.');
    }

    private function selectedPackage(Request $request): ?Departure
    {
        $id = $request->session()->get(self::SELECTED_PACKAGE);

        return $id ? $this->availablePackages()->whereKey($id)->first() : null;
    }

    private function availablePackages(): Builder
    {
        return Departure::query()
            ->with(['branch', 'hotels', 'itineraries'])
            ->withCount(['registrations as active_registrations_count' => fn (Builder $query) => $query->where('status', '!=', 'cancelled')])
            ->where('is_public', true)
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', today())
            ->orderBy('departure_date');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        return str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;
    }
}
