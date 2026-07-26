<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'audit_log_retention_days'],
            [
                'value' => '365',
                'type' => 'integer',
                'group' => 'audit_security',
                'label' => 'Masa Simpan Audit Log (hari)',
                'description' => 'Log lebih lama dari durasi ini dapat dibersihkan oleh Super Admin. Default 365 hari.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'audit_log_retention_days')->delete();
    }
};
