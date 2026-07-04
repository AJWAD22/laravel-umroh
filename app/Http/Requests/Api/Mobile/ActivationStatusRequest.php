<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ActivationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'public_id' => ['required', 'uuid'],
            'claim_secret' => ['required', 'string', 'size:64'],
            'device_uuid' => ['required', 'string', 'max:120'],
        ];
    }
}
