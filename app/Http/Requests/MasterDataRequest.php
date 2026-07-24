<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Services\MasterDataService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = app(MasterDataService::class)
            ->definitionFor($this->route('resource'))['permission'];

        return $this->user()->can($permission);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()->hasRole(UserRole::SuperAdmin->value)
            && $this->route('resource') !== 'branches') {
            $this->merge(['branch_id' => $this->user()->branch_id]);
        }
    }

    public function rules(): array
    {
        $resource = $this->route('resource');
        $id = (int) ($this->route('record') ?? 0);
        $branchId = (int) ($this->input('branch_id') ?: $this->user()->branch_id);
        $unique = fn (string $table, string $column) => Rule::unique($table, $column)->ignore($id);
        $staff = match ($resource) {
            'tour-leaders' => TourLeader::query()->with('user')->find($id),
            'muthawwifs' => Muthawwif::query()->with('user')->find($id),
            default => null,
        };
        $staffUserId = $staff?->user_id;

        return match ($resource) {
            'branches' => [
                'code' => ['required', 'string', 'max:20', $unique('branches', 'code')],
                'name' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:100'],
                'province' => ['nullable', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ],
            'branch-admins' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', $unique('users', 'email')],
                'phone_number' => ['nullable', 'string', 'max:30'],
                'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
                'is_active' => ['required', 'boolean'],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ],
            'pilgrims' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'group_id' => ['nullable', Rule::exists('groups', 'id')->where('branch_id', $branchId)],
                'registration_number' => ['exclude'],
                'full_name' => ['required', 'string', 'max:255'],
                'nik' => ['nullable', 'string', 'max:20'],
                'passport_number' => ['nullable', 'string', 'max:30'],
                'passport_expired_at' => ['nullable', 'date'],
                'gender' => ['required', Rule::in(['male', 'female'])],
                'phone' => ['nullable', 'string', 'max:30'],
                'birth_date' => ['nullable', 'date', 'before:today'],
                'address' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['registered', 'active', 'completed', 'cancelled'])],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ],
            'tour-leaders', 'muthawwifs' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'employee_number' => ['exclude'],
                'full_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staffUserId)],
                'password' => [! $staffUserId ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
                'languages' => [$resource === 'muthawwifs' ? 'nullable' : 'exclude', 'string'],
                'is_active' => ['required', 'boolean'],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ],
            'hotels' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'name' => ['required', 'string', 'max:255'],
                'city' => ['required', Rule::in(['makkah', 'madinah', 'other'])],
                'address' => ['nullable', 'string'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'geofence_radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            ],
            'checkpoints' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'departure_id' => ['nullable', Rule::exists('departures', 'id')->where('branch_id', $branchId)],
                'group_id' => ['nullable', Rule::exists('groups', 'id')->where('branch_id', $branchId)],
                'name' => ['required', 'string', 'max:255'],
                'category' => ['required', Rule::in(['ibadah', 'hotel', 'titik_kumpul', 'kesehatan', 'transportasi', 'belanja', 'lainnya'])],
                'city' => ['required', Rule::in(['makkah', 'madinah', 'jeddah', 'other'])],
                'address' => ['nullable', 'string'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'geofence_radius_meters' => ['nullable', 'integer', 'min:10', 'max:10000'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['required', 'boolean'],
            ],
            'departures' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'code' => ['exclude'],
                'program_name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'facilities' => ['nullable', 'string', 'max:3000'],
                'requirements' => ['nullable', 'string', 'max:3000'],
                'departure_date' => ['required', 'date'],
                'return_date' => ['required', 'date', 'after:departure_date'],
                'departure_airport' => ['nullable', 'string', 'max:100'],
                'arrival_airport' => ['nullable', 'string', 'max:100'],
                'airline' => ['nullable', 'string', 'max:120'],
                'flight_number' => ['nullable', 'string', 'max:80'],
                'price' => ['nullable', 'integer', 'min:0'],
                'is_public' => ['required', 'boolean'],
                'hotel_ids' => ['nullable', 'array'],
                'hotel_ids.*' => [Rule::exists('hotels', 'id')->where('branch_id', $branchId)],
                'itinerary_plan' => ['nullable', 'string'],
                'quota' => ['nullable', 'integer', 'min:1'],
                'status' => ['required', Rule::in(['draft', 'scheduled', 'departed', 'completed', 'cancelled'])],
            ],
            'groups' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'departure_id' => ['nullable', Rule::exists('departures', 'id')->where('branch_id', $branchId)],
                'tour_leader_id' => ['nullable', Rule::exists('tour_leaders', 'id')->where('branch_id', $branchId)],
                'muthawwif_id' => ['nullable', Rule::exists('muthawwifs', 'id')->where('branch_id', $branchId)],
                'code' => ['exclude'],
                'name' => ['required', 'string', 'max:255'],
                'capacity' => ['nullable', 'integer', 'min:1'],
                'notes' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ],
            default => abort(404),
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->route('resource') === 'checkpoints'
                && $this->filled('group_id')
                && $this->filled('departure_id')) {
                $matches = Group::query()
                    ->whereKey($this->integer('group_id'))
                    ->where('departure_id', $this->integer('departure_id'))
                    ->exists();

                if (! $matches) {
                    $validator->errors()->add(
                        'group_id',
                        'Rombongan harus berasal dari jadwal perjalanan yang dipilih.',
                    );
                }
            }

            if ($this->route('resource') === 'pilgrims') {
                $id = (int) ($this->route('record') ?? 0);

                if ($this->filled('nik')) {
                    $exists = Pilgrim::query()
                        ->where('nik_hash', Pilgrim::identityDigest($this->input('nik')))
                        ->when($id, fn ($query) => $query->whereKeyNot($id))
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('nik', 'NIK sudah digunakan jamaah lain.');
                    }
                }

                if ($this->filled('passport_number')) {
                    $exists = Pilgrim::query()
                        ->where('passport_number_hash', Pilgrim::identityDigest($this->input('passport_number')))
                        ->when($id, fn ($query) => $query->whereKeyNot($id))
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('passport_number', 'Nomor paspor sudah digunakan jamaah lain.');
                    }
                }
            }

            if ($this->route('resource') !== 'departures' || ! $this->filled('itinerary_plan')) {
                return;
            }

            $duration = CarbonImmutable::parse($this->input('departure_date'))
                ->diffInDays(CarbonImmutable::parse($this->input('return_date'))) + 1;
            $days = [];

            foreach (preg_split('/\r\n|\r|\n/', (string) $this->input('itinerary_plan')) as $index => $line) {
                if (blank(trim($line))) {
                    continue;
                }

                $parts = array_map('trim', explode('|', $line, 4));
                $day = is_numeric($parts[0] ?? null) ? (int) $parts[0] : $index + 1;

                if ($day < 1 || $day > $duration) {
                    $validator->errors()->add(
                        'itinerary_plan',
                        "Hari ke-{$day} berada di luar rentang paket {$duration} hari.",
                    );

                    return;
                }

                if (in_array($day, $days, true)) {
                    $validator->errors()->add(
                        'itinerary_plan',
                        "Jadwal hari ke-{$day} ditulis lebih dari satu kali.",
                    );

                    return;
                }

                $days[] = $day;
            }
        });
    }
}
