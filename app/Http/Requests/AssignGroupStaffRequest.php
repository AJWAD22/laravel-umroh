<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignGroupStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        $branchId = $this->route('group')->branch_id;

        return [
            'tour_leader_id' => [
                'nullable',
                Rule::exists('tour_leaders', 'id')->where(
                    fn ($query) => $query
                        ->where('branch_id', $branchId)
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ],
            'muthawwif_id' => [
                'nullable',
                Rule::exists('muthawwifs', 'id')->where(
                    fn ($query) => $query
                        ->where('branch_id', $branchId)
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ],
        ];
    }
}
