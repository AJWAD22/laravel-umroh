<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'status' => ['nullable', Rule::in(['active', 'acknowledged', 'resolved', 'cancelled'])],
        ];
    }
}
