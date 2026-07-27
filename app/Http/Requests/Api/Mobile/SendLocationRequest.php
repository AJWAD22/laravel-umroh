<?php

namespace App\Http\Requests\Api\Mobile;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $value = $this->input('recorded_at');
            if (! $value) {
                return;
            }

            try {
                $recordedAt = CarbonImmutable::parse($value);
            } catch (\Throwable) {
                return;
            }

            $now = CarbonImmutable::now();

            if ($recordedAt->gt($now->addMinutes(5))) {
                $validator->errors()->add('recorded_at', 'Waktu perangkat tidak boleh lebih dari 5 menit ke depan.');
            }

            if ($recordedAt->lt($now->subDay())) {
                $validator->errors()->add('recorded_at', 'Waktu perangkat tidak boleh lebih lama dari 24 jam.');
            }
        });
    }
}
