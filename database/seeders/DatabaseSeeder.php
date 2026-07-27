<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder utama yang dipanggil oleh `php artisan db:seed`.
     * Seeder utama aman dijalankan ulang di production. Data demo hanya
     * boleh dibuat secara eksplisit pada environment local/testing.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SystemSettingSeeder::class);

        if (app()->environment(['local', 'testing']) && env('SEED_DEMO_DATA', false)) {
            $this->call(DemoMasterDataSeeder::class);
            $this->call(MobileDemoSeeder::class);
        }
    }
}
