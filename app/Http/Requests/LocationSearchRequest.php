<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('checkpoints.manage')
            || $this->user()->can('hotels.manage');
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:160'],
        ];
    }
}
