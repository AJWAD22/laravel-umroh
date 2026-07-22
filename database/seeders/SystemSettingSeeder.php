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
            ['key' => 'company_tagline', 'value' => 'Perjalanan umroh yang terencana, nyaman, dan terpantau dari keberangkatan hingga kepulangan.', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Tagline Travel', 'description' => 'Kalimat utama yang tampil pada landing page.'],
            ['key' => 'company_about', 'value' => 'Kami mendampingi jamaah menjalankan ibadah umroh dengan layanan perjalanan yang transparan, pembimbing berpengalaman, serta dukungan teknologi monitoring rombongan.', 'type' => 'textarea', 'group' => 'travel_profile', 'label' => 'Tentang Travel', 'description' => 'Profil singkat travel pada landing page.'],
            ['key' => 'company_address', 'value' => '', 'type' => 'textarea', 'group' => 'travel_profile', 'label' => 'Alamat Kantor', 'description' => 'Alamat resmi kantor travel.'],
            ['key' => 'company_whatsapp', 'value' => '', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'WhatsApp Travel', 'description' => 'Nomor WhatsApp dengan kode negara, contoh 6281234567890.'],
            ['key' => 'company_license', 'value' => '', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Nomor Izin PPIU', 'description' => 'Isi hanya dengan nomor izin resmi yang sudah terverifikasi.'],
            ['key' => 'company_website', 'value' => '', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Website Travel', 'description' => 'Alamat website resmi travel.'],
            ['key' => 'office_hours', 'value' => 'Senin–Sabtu, 08.00–17.00', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Jam Layanan', 'description' => 'Jam operasional yang ditampilkan kepada calon jamaah.'],
            ['key' => 'gps_offline_threshold_minutes', 'value' => '10', 'type' => 'integer', 'group' => 'monitoring', 'label' => 'Batas GPS Offline (menit)', 'description' => 'Perangkat dianggap offline setelah tidak mengirim lokasi selama durasi ini.'],
            ['key' => 'monitoring_refresh_seconds', 'value' => '30', 'type' => 'integer', 'group' => 'monitoring', 'label' => 'Refresh Monitoring (detik)', 'description' => 'Interval refresh standar dashboard monitoring.'],
            ['key' => 'default_geofence_radius_meters', 'value' => '250', 'type' => 'integer', 'group' => 'monitoring', 'label' => 'Radius Geofence Default (meter)', 'description' => 'Radius awal ketika membuat area aman.'],
        ];
    }
}
