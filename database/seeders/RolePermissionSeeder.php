<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\MobileRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.national.view',
            'dashboard.branch.view',
            'branches.manage',
            'branch-admins.manage',
            'pilgrims.manage',
            'pilgrims.view',
            'tour-leaders.manage',
            'tour-leaders.view',
            'muthawwifs.manage',
            'muthawwifs.view',
            'departures.manage',
            'departures.view',
            'groups.manage',
            'groups.view',
            'hotels.manage',
            'hotels.view',
            'checkpoints.manage',
            'checkpoints.view',
            'monitoring.view',
            'tracking-history.view',
            'tracking.live.view',
            'tracking.history.view',
            'sos.handle',
            'registrations.manage',
            'registrations.view',
            'registrations.approve',
            'payments.verify',
            'activation.reset',
            'reports.view',
            'system-settings.manage',
            'audit.branch.view',
            'audit.global.view',
            'profile.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(UserRole::SuperAdmin->value, 'web')->syncPermissions([
            'dashboard.national.view',
            'branches.manage',
            'branch-admins.manage',
            'pilgrims.view',
            'tour-leaders.view',
            'muthawwifs.view',
            'departures.view',
            'groups.view',
            'hotels.view',
            'registrations.view',
            'reports.view',
            'system-settings.manage',
            'audit.global.view',
            'profile.manage',
        ]);

        Role::findOrCreate(UserRole::BranchAdmin->value, 'web')->syncPermissions([
            'dashboard.branch.view',
            'pilgrims.manage',
            'tour-leaders.manage',
            'muthawwifs.manage',
            'departures.manage',
            'groups.manage',
            'hotels.manage',
            'checkpoints.manage',
            'monitoring.view',
            'tracking-history.view',
            'tracking.live.view',
            'tracking.history.view',
            'sos.handle',
            'registrations.manage',
            'reports.view',
            'registrations.approve',
            'payments.verify',
            'activation.reset',
            'audit.branch.view',
            'profile.manage',
        ]);

        foreach (MobileRole::cases() as $mobileRole) {
            Role::findOrCreate($mobileRole->value, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
