<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class SendLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            // Jam perangkat dapat berbeda beberapa detik dari jam server.
            // Controller menormalisasi waktu masa depan ke waktu server agar
            // tracking tidak gagal hanya karena clock skew pada ponsel.
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
