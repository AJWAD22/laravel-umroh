<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('system-settings.manage');
    }

    public function rules(): array
    {
        return [
            'application_name' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:150'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'gps_offline_threshold_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'monitoring_refresh_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'default_geofence_radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
        ];
    }
}
