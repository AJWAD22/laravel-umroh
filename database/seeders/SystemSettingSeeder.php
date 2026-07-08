<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->defaults() as $setting) {
            SystemSetting::query()->firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    private function defaults(): array
    {
        return [
            ['key' => 'application_name', 'value' => 'Mantau Umroh', 'type' => 'string', 'group' => 'general', 'label' => 'Nama Aplikasi', 'description' => 'Nama yang tampil pada panel administrasi.'],
            ['key' => 'company_name', 'value' => 'Travel Umrah', 'type' => 'string', 'group' => 'general', 'label' => 'Nama Perusahaan', 'description' => 'Nama resmi penyelenggara perjalanan.'],
            ['key' => 'support_email', 'value' => 'support@example.com', 'type' => 'email', 'group' => 'general', 'label' => 'Email Dukungan', 'description' => 'Kontak dukungan operasional.'],
            ['key' => 'support_phone', 'value' => '', 'type' => 'string', 'group' => 'general', 'label' => 'Telepon Dukungan', 'description' => 'Nomor telepon pusat bantuan.'],
            ['key' => 'gps_offline_threshold_minutes', 'value' => '10', 'type' => 'integer', 'group' => 'monitoring', 'label' => 'Batas GPS Offline (menit)', 'description' => 'Perangkat dianggap offline setelah tidak mengirim lokasi selama durasi ini.'],
            ['key' => 'monitoring_refresh_seconds', 'value' => '30', 'type' => 'integer', 'group' => 'monitoring', 'label' => 'Refresh Monitoring (detik)', 'description' => 'Interval refresh standar dashboard monitoring.'],
            ['key' => 'default_geofence_radius_meters', 'value' => '250', 'type' => 'integer', 'group' => 'monitoring', 'label' => 'Radius Geofence Default (meter)', 'description' => 'Radius awal ketika membuat area aman.'],
        ];
    }
}
