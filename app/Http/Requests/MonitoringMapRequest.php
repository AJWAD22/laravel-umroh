<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoringMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('monitoring.view');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()->hasRole(UserRole::SuperAdmin->value)) {
            $this->merge(['branch_id' => $this->user()->branch_id]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'status' => ['nullable', Rule::in(['online', 'offline', 'sos'])],
        ];
    }
}
