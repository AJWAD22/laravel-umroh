<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'string', 'max:120'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['android', 'ios'])],
            'fcm_token' => ['required', 'string', 'max:4096'],
        ];
    }
}
