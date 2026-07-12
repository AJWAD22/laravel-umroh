<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SystemSettingSeeder::class);

        $branch = Branch::query()->firstOrCreate(
            ['code' => 'BJM'],
            [
                'name' => 'Cabang Banjarmasin',
                'city' => 'Banjarmasin',
                'province' => 'Kalimantan Selatan',
            ],
        );

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@umrah.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles('super-admin');

        $branchAdmin = User::query()->updateOrCreate(
            ['email' => 'admin.cabang@umrah.test'],
            [
                'name' => 'Admin Cabang',
                'password' => 'password',
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
            ],
        );
        $branchAdmin->syncRoles('admin-cabang');

        $this->call(DemoMasterDataSeeder::class);

        if (app()->environment(['local', 'testing']) && env('SEED_DEMO_DATA', false)) {
            $this->call(MobileDemoSeeder::class);
        }
    }
}
