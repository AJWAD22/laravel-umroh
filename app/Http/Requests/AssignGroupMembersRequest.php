<?php

namespace App\Http\Requests;

use App\Models\GroupMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignGroupMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'pilgrim_ids' => ['required', 'array', 'min:1'],
            'pilgrim_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('pilgrims', 'id')->where(
                    fn ($query) => $query
                        ->where('branch_id', $this->route('group')->branch_id)
                        ->whereNull('deleted_at')
                        ->whereIn('status', ['registered', 'active']),
                ),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $group = $this->route('group');
                $pilgrimIds = collect($this->input('pilgrim_ids', []))->map(fn ($id) => (int) $id);

                $conflicts = GroupMember::query()
                    ->whereIn('pilgrim_id', $pilgrimIds)
                    ->where('status', 'active')
                    ->where('group_id', '!=', $group->id)
                    ->exists();

                if ($conflicts) {
                    $validator->errors()->add(
                        'pilgrim_ids',
                        'Salah satu jamaah sudah menjadi anggota rombongan aktif lain.',
                    );
                }

                if ($group->capacity) {
                    $currentMembers = $group->members()->where('status', 'active')->count();
                    $newMembers = $pilgrimIds->diff(
                        $group->members()->where('status', 'active')->pluck('pilgrim_id'),
                    )->count();

                    if ($currentMembers + $newMembers > $group->capacity) {
                        $validator->errors()->add('pilgrim_ids', 'Jumlah anggota melebihi kapasitas group.');
                    }
                }
            },
        ];
    }
}
