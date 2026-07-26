<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $supportPhone = DB::table('system_settings')->where('key', 'support_phone')->value('value');
        $legacyWhatsapp = DB::table('system_settings')->where('key', 'company_whatsapp')->value('value');

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'support_phone'],
            [
                'value' => filled($supportPhone) ? $supportPhone : ($legacyWhatsapp ?: '6285947566363'),
                'type' => 'string',
                'group' => 'general',
                'label' => 'Telepon / WhatsApp Pusat',
                'description' => 'Satu nomor pusat untuk telepon dan tombol WhatsApp landing page. Gunakan format 628xxxxxxxxxx.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('system_settings')->where('key', 'company_whatsapp')->delete();
    }

    public function down(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'company_whatsapp'],
            [
                'value' => DB::table('system_settings')->where('key', 'support_phone')->value('value') ?: '',
                'type' => 'string',
                'group' => 'travel_profile',
                'label' => 'Nomor WhatsApp',
                'description' => 'Gunakan format kode negara, contoh 6281234567890.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
};
