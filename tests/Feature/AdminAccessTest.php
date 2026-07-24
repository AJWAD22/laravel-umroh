<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\AuditLog;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\User;
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

    public function test_super_admin_cannot_open_operational_monitoring_or_reset_pilgrim_pin(): void
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
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'BJM-DEP-TEST-001',
            'program_name' => 'Paket Uji Akses',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(9),
            'status' => 'scheduled',
        ]);
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'code' => 'BJM-GRP-TEST-001',
            'name' => 'Rombongan Uji Akses',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('monitoring.map.index'))
            ->assertForbidden();
        $this->actingAs($superAdmin)
            ->get(route('monitoring.tracking.index'))
            ->assertForbidden();
        $this->actingAs($superAdmin)
            ->get(route('monitoring.sos.index'))
            ->assertForbidden();
        $this->actingAs($superAdmin)
            ->post(route('master-data.pilgrims.regenerate-pin', $pilgrim))
            ->assertForbidden();
        $this->actingAs($superAdmin)
            ->post(route('groups.reset-pins', $group))
            ->assertForbidden();
    }

    public function test_super_admin_cannot_open_branch_registration_operations(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $superAdmin = User::factory()->create(['branch_id' => null]);
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('registrations.index'))
            ->assertForbidden();
    }

    public function test_branch_admin_can_open_branch_registration_operations(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create(['code' => 'REG', 'name' => 'Cabang Registrasi', 'city' => 'Makassar']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole('admin-cabang');

        $this->actingAs($admin)
            ->get(route('registrations.index'))
            ->assertOk()
            ->assertSee('Pendaftaran Jamaah');
    }

    public function test_audit_log_visibility_is_scoped_by_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'AUD-A', 'name' => 'Cabang Audit A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'AUD-B', 'name' => 'Cabang Audit B', 'city' => 'Jakarta']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $adminA = User::factory()->create(['branch_id' => $branchA->id]);
        $adminA->assignRole('admin-cabang');

        AuditLog::create([
            'branch_id' => $branchA->id,
            'actor_id' => $adminA->id,
            'action' => 'groups.members.assigned',
        ]);
        AuditLog::create([
            'branch_id' => $branchB->id,
            'action' => 'activation.group_pins.reset',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('groups.members.assigned')
            ->assertSee('activation.group_pins.reset');

        $this->actingAs($adminA)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('groups.members.assigned')
            ->assertDontSee('activation.group_pins.reset');
    }
}
