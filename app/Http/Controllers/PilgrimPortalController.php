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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class PilgrimPortalController extends Controller
{
    private const SELECTED_PACKAGE = 'pilgrim_portal_selected_package';
    private const QUOTA_BLOCKING_STATUSES = ['submitted', 'revision_requested', 'approved', 'in_group'];

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
                'required',
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
                'email' => $data['email'],
                'phone_number' => $phone,
                'password' => $data['password'],
                'is_active' => true,
            ]);
            PilgrimPortalAccount::query()->create([
                'user_id' => $user->id,
                'phone' => $phone,
                'email' => $data['email'],
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

        $account = $request->user()->portalAccount;
        if ($request->user()->portalRegistrations()
            ->where('departure_id', $departure->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists()) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'Anda sudah mendaftar pada paket tersebut.');
        }

        $registration = $request->user()->portalRegistrations()
            ->where('departure_id', $departure->id)
            ->whereIn('status', ['cancelled', 'rejected'])
            ->latest()
            ->first();

        $registration?->forceFill([
            'branch_id' => $departure->branch_id,
            'full_name' => $request->user()->name,
            'phone' => $account?->phone ?: $request->user()->phone_number,
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'submitted_at' => null,
            'revision_notes' => null,
        ])->save();

        $registration ??= PilgrimRegistration::query()->create([
            'user_id' => $request->user()->id,
            'branch_id' => $departure->branch_id,
            'departure_id' => $departure->id,
            'full_name' => $request->user()->name,
            'phone' => $account?->phone ?: $request->user()->phone_number,
            'status' => 'draft',
            'payment_status' => 'unpaid',
        ]);

        $request->session()->put(self::SELECTED_PACKAGE, $registration->id);

        return redirect()->route('portal.biodata.edit');
    }

    public function biodata(Request $request): View|RedirectResponse
    {
        $registration = $this->currentRegistration($request);
        if (! $registration) {
            return redirect()->route('portal.packages.index')
                ->with('error', 'Pilih paket perjalanan sebelum mengisi biodata.');
        }

        return view('portal.biodata', [
            'registration' => $registration,
            'package' => $registration->departure,
            'account' => $request->user()->portalAccount,
        ]);
    }

    public function submitBiodata(Request $request): RedirectResponse
    {
        $registration = $this->currentRegistration($request);
        if (! $registration) {
            return redirect()->route('portal.packages.index')
                ->with('error', 'Sesi paket berakhir. Silakan pilih paket kembali.');
        }

        $isDraft = $request->input('action') === 'draft';
        $required = $isDraft ? 'nullable' : 'required';
        $data = $request->validate([
            'full_name' => [$required, 'string', 'max:255'],
            'nik' => [$required, 'digits_between:12,20'],
            'gender' => [$required, Rule::in(['male', 'female'])],
            'birth_date' => [$required, 'date', 'before:today'],
            'passport_number' => ['nullable', 'string', 'max:30'],
            'passport_expired_at' => ['nullable', 'date', 'after:today'],
            'address' => [$required, 'string', 'max:1500'],
            'emergency_contact_name' => [$required, 'string', 'max:255'],
            'emergency_contact_phone' => [$required, 'string', 'max:30'],
            'health_notes' => ['nullable', 'string', 'max:1500'],
            'document_notes' => ['nullable', 'string', 'max:1500'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'identity_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'passport_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'confirmation' => [$isDraft ? 'nullable' : 'accepted'],
        ]);
        unset($data['confirmation']);
        foreach (['photo', 'identity_document', 'passport_document'] as $fileField) {
            unset($data[$fileField]);
        }

        $fileData = $this->storeRegistrationFiles($request);

        DB::transaction(function () use ($request, $registration, $data, $fileData, $isDraft): void {
            $registration = PilgrimRegistration::query()
                ->where('user_id', $request->user()->id)
                ->whereKey($registration->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedDeparture = Departure::query()->lockForUpdate()->findOrFail($registration->departure_id);

            if ($isDraft) {
                $registration->fill(array_merge($data, $fileData));
                $registration->status = 'draft';
                $registration->payment_status = $registration->payment_status ?: 'unpaid';
                $registration->save();

                return;
            }

            $activeRegistrations = PilgrimRegistration::query()
                ->where('departure_id', $lockedDeparture->id)
                ->whereKeyNot($registration->id)
                ->whereIn('status', self::QUOTA_BLOCKING_STATUSES)
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
                ->whereKeyNot($registration->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->where(fn (Builder $query) => $query
                    ->where('user_id', $request->user()->id)
                    ->orWhere('nik_hash', PilgrimRegistration::identityDigest($data['nik'])))
                ->exists()) {
                throw ValidationException::withMessages([
                    'confirmation' => ['Akun atau NIK ini sudah terdaftar pada paket yang dipilih.'],
                ]);
            }

            $registration->fill(array_merge($data, $fileData));
            $registration->forceFill([
                'branch_id' => $lockedDeparture->branch_id,
                'phone' => $request->user()->portalAccount->phone,
                'status' => 'submitted',
                'payment_status' => 'unpaid',
                'revision_notes' => null,
                'submitted_at' => now(),
            ])->save();
            $request->user()->forceFill(['name' => $data['full_name']])->save();
        });

        if ($isDraft) {
            return redirect()->route('portal.dashboard')
                ->with('success', 'Draft biodata berhasil disimpan. Anda dapat melanjutkannya kapan saja.');
        }

        return redirect()->route('portal.dashboard')
            ->with('success', 'Pendaftaran berhasil dikirim. Admin cabang akan memverifikasi biodata dan dokumen Anda.');
    }

    private function currentRegistration(Request $request): ?PilgrimRegistration
    {
        $query = $request->user()->portalRegistrations()
            ->with(['branch', 'departure.branch', 'departure.hotels', 'departure.itineraries'])
            ->whereIn('status', ['draft', 'revision_requested']);
        $id = $request->session()->get(self::SELECTED_PACKAGE);

        return $id
            ? (clone $query)->whereKey($id)->first()
            : $query->latest()->first();
    }

    private function availablePackages(): Builder
    {
        return Departure::query()
            ->with(['branch', 'hotels', 'itineraries'])
            ->withCount(['registrations as active_registrations_count' => fn (Builder $query) => $query->whereIn('status', self::QUOTA_BLOCKING_STATUSES)])
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

    /** @return array<string, string> */
    private function storeRegistrationFiles(Request $request): array
    {
        $paths = [];
        foreach ([
            'photo' => 'photo_path',
            'identity_document' => 'identity_document_path',
            'passport_document' => 'passport_document_path',
        ] as $input => $column) {
            if ($request->hasFile($input)) {
                $paths[$column] = $request->file($input)->store('pilgrim-registration-documents', 'public');
            }
        }

        return $paths;
    }
}
