<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Pilgrim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackingHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('tracking-history.view')) {
            return false;
        }

        if ($this->user()->hasRole(UserRole::SuperAdmin->value) || ! $this->filled('pilgrim_id')) {
            return true;
        }

        return Pilgrim::query()
            ->whereKey($this->integer('pilgrim_id'))
            ->where('branch_id', $this->user()->branch_id)
            ->exists();
    }

    public function rules(): array
    {
        $branchId = $this->user()->hasRole(UserRole::SuperAdmin->value)
            ? null
            : $this->user()->branch_id;

        return [
            'pilgrim_id' => [
                'required',
                'integer',
                Rule::exists('pilgrims', 'id')->where(
                    fn ($query) => $query
                        ->when($branchId, fn ($scoped) => $scoped->where('branch_id', $branchId))
                        ->whereNull('deleted_at'),
                ),
            ],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}
