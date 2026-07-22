<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['key' => 'company_tagline', 'value' => 'Perjalanan ibadah yang terencana, terpantau, dan penuh ketenangan.', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Tagline Travel', 'description' => 'Kalimat singkat yang tampil pada bagian utama landing page.'],
            ['key' => 'company_about', 'value' => 'Kami membantu jamaah mempersiapkan perjalanan umroh melalui informasi paket yang jelas, pendampingan petugas, serta sistem monitoring selama perjalanan.', 'type' => 'textarea', 'group' => 'travel_profile', 'label' => 'Tentang Travel', 'description' => 'Profil singkat penyelenggara perjalanan pada landing page.'],
            ['key' => 'company_address', 'value' => '', 'type' => 'textarea', 'group' => 'travel_profile', 'label' => 'Alamat Kantor Pusat', 'description' => 'Alamat resmi kantor travel.'],
            ['key' => 'company_whatsapp', 'value' => '', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Nomor WhatsApp', 'description' => 'Gunakan format kode negara, contoh 6281234567890.'],
            ['key' => 'company_license', 'value' => '', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Nomor Izin PPIU', 'description' => 'Isi hanya dengan nomor izin resmi dan terverifikasi.'],
            ['key' => 'company_website', 'value' => '', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Website Resmi', 'description' => 'Alamat website resmi travel jika berbeda dari aplikasi ini.'],
            ['key' => 'office_hours', 'value' => 'Senin–Sabtu, 08.00–17.00', 'type' => 'string', 'group' => 'travel_profile', 'label' => 'Jam Layanan', 'description' => 'Jam layanan kantor dan konsultasi jamaah.'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [...$setting, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'company_tagline',
            'company_about',
            'company_address',
            'company_whatsapp',
            'company_license',
            'company_website',
            'office_hours',
        ])->delete();
    }
};
