<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Pilgrim;
use App\Models\User;
use App\Services\MobileActivationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_super_admin_can_open_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pusat Kendali Nasional');
    }

    public function test_non_admin_cannot_open_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_super_admin_can_generate_pilgrim_activation_pin_across_branches(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $superAdmin = User::factory()->create(['branch_id' => null]);
        $superAdmin->assignRole('super-admin');
        $branch = Branch::create([
            'code' => 'BJM',
            'name' => 'Cabang Banjarmasin',
            'city' => 'Banjarmasin',
            'province' => 'Kalimantan Selatan',
            'is_active' => true,
        ]);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => 'BJM-JAM-TEST-001',
            'full_name' => 'Ahmad Fauzi',
            'gender' => 'male',
            'status' => 'registered',
            'monitoring_status' => 'normal',
        ]);

        $pin = app(MobileActivationService::class)->generatePin($superAdmin, $pilgrim);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $pin);
        $this->assertDatabaseHas('pilgrims', [
            'id' => $pilgrim->id,
            'activation_pin_created_by' => $superAdmin->id,
        ]);
    }
}
