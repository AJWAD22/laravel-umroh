<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'staff_geofence_enabled',
                'staff_geofence_radius_meters',
                'staff_geofence_fresh_minutes',
            ])
            ->delete();
    }

    public function down(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            [
                'key' => 'staff_geofence_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'monitoring',
                'label' => 'Geofence Mengikuti Petugas',
                'description' => 'Nonaktif secara bawaan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'staff_geofence_radius_meters',
                'value' => '150',
                'type' => 'integer',
                'group' => 'monitoring',
                'label' => 'Radius Aman dari Petugas (meter)',
                'description' => 'Nonaktif secara bawaan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'staff_geofence_fresh_minutes',
                'value' => '5',
                'type' => 'integer',
                'group' => 'monitoring',
                'label' => 'Batas GPS Petugas Aktif (menit)',
                'description' => 'Nonaktif secara bawaan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
