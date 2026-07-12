<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SystemSettingSeeder::class);

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@umrah.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles('super-admin');

        $this->call(DemoMasterDataSeeder::class);

        if (app()->environment(['local', 'testing']) && env('SEED_DEMO_DATA', false)) {
            $this->call(MobileDemoSeeder::class);
        }
    }
}
