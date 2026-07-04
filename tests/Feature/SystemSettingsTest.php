<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use App\Services\SystemSettingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_system_settings_and_cache_is_refreshed(): void
    {
        [$superAdmin] = $this->users();
        $service = app(SystemSettingService::class);
        $this->assertSame(10, $service->get('gps_offline_threshold_minutes'));

        $this->actingAs($superAdmin)
            ->put(route('settings.system.update'), [
                'application_name' => 'Umrah Command Center',
                'company_name' => 'PT Travel Amanah',
                'support_email' => 'support@amanah.test',
                'support_phone' => '0800123456',
                'gps_offline_threshold_minutes' => 15,
                'monitoring_refresh_seconds' => 20,
                'default_geofence_radius_meters' => 500,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(15, $service->get('gps_offline_threshold_minutes'));
        $this->assertSame('Umrah Command Center', $service->get('application_name'));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_geofence_radius_meters',
            'value' => '500',
        ]);
    }

    public function test_branch_admin_cannot_open_or_update_system_settings(): void
    {
        [, $branchAdmin] = $this->users();

        $this->actingAs($branchAdmin)
            ->get(route('settings.system.edit'))
            ->assertForbidden();

        $this->actingAs($branchAdmin)
            ->put(route('settings.system.update'))
            ->assertForbidden();
    }

    public function test_profile_phone_and_dedicated_password_page_are_available(): void
    {
        [$superAdmin] = $this->users();

        $this->actingAs($superAdmin)
            ->patch(route('profile.update'), [
                'name' => 'Super Admin Updated',
                'email' => $superAdmin->email,
                'phone_number' => '081234567890',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('081234567890', $superAdmin->fresh()->phone_number);

        $this->actingAs($superAdmin)
            ->get(route('settings.password'))
            ->assertOk()
            ->assertSee('Ganti Password');
    }

    /**
     * @return array{User, User}
     */
    private function users(): array
    {
        $this->seed([RolePermissionSeeder::class, SystemSettingSeeder::class]);
        $branch = Branch::firstOrCreate(
            ['code' => 'SET-A'],
            ['name' => 'Cabang Pengaturan', 'city' => 'Makassar'],
        );

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);
        $branchAdmin = User::factory()->create(['branch_id' => $branch->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        return [$superAdmin, $branchAdmin];
    }
}
