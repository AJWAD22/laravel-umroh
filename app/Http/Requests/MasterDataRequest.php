<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Services\MasterDataService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'registration_number' => ['required', 'string', 'max:40', $unique('pilgrims', 'registration_number')],
                'full_name' => ['required', 'string', 'max:255'],
                'nik' => ['nullable', 'string', 'max:20', $unique('pilgrims', 'nik')],
                'passport_number' => ['nullable', 'string', 'max:30', $unique('pilgrims', 'passport_number')],
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
                'employee_number' => ['required', 'string', 'max:40', $unique($resource === 'tour-leaders' ? 'tour_leaders' : 'muthawwifs', 'employee_number')],
                'full_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
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
            'departures' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'code' => ['required', 'string', 'max:40', $unique('departures', 'code')],
                'program_name' => ['required', 'string', 'max:255'],
                'departure_date' => ['required', 'date'],
                'return_date' => ['required', 'date', 'after:departure_date'],
                'departure_airport' => ['nullable', 'string', 'max:100'],
                'arrival_airport' => ['nullable', 'string', 'max:100'],
                'quota' => ['nullable', 'integer', 'min:1'],
                'status' => ['required', Rule::in(['draft', 'scheduled', 'departed', 'completed', 'cancelled'])],
            ],
            'groups' => [
                'branch_id' => ['required', 'exists:branches,id'],
                'departure_id' => ['required', Rule::exists('departures', 'id')->where('branch_id', $branchId)],
                'tour_leader_id' => ['nullable', Rule::exists('tour_leaders', 'id')->where('branch_id', $branchId)],
                'muthawwif_id' => ['nullable', Rule::exists('muthawwifs', 'id')->where('branch_id', $branchId)],
                'code' => ['required', 'string', 'max:40', $unique('groups', 'code')],
                'name' => ['required', 'string', 'max:255'],
                'capacity' => ['nullable', 'integer', 'min:1'],
                'notes' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ],
            default => abort(404),
        };
    }
}
