<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ClaimActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:80'],
            'numeric_code' => ['required', 'digits:6'],
            'device_uuid' => ['required', 'string', 'max:120'],
            'device_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios'],
        ];
    }
}
