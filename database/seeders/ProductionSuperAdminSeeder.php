<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        DB::transaction(function (): void {
            $primary = User::query()->updateOrCreate(
                ['email' => 'superadmin@mantauumrah.id'],
                [
                    'branch_id' => null,
                    'name' => 'Super Admin',
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            User::role(UserRole::SuperAdmin->value)
                ->whereKeyNot($primary->id)
                ->get()
                ->each(function (User $user): void {
                    $user->tokens()->delete();
                    $user->removeRole(UserRole::SuperAdmin->value);
                    $user->forceFill(['is_active' => false])->save();
                });

            $primary->syncRoles(UserRole::SuperAdmin->value);
        });

        $this->command?->warn('Super Admin aktif: superadmin@mantauumrah.id');
        $this->command?->warn('Segera ganti password awal melalui menu Profil Saya.');
    }
}
