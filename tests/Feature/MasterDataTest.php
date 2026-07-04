<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_every_master_data_module(): void
    {
        $superAdmin = $this->superAdmin();

        foreach (['branches', 'branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs', 'hotels', 'departures', 'groups'] as $resource) {
            $this->actingAs($superAdmin)
                ->get(route('master-data.index', $resource))
                ->assertOk();
        }
    }

    public function test_super_admin_can_create_a_branch_admin_with_the_correct_role(): void
    {
        $superAdmin = $this->superAdmin();
        $branch = Branch::create(['code' => 'BJM', 'name' => 'Banjarmasin', 'city' => 'Banjarmasin']);

        $this->actingAs($superAdmin)
            ->post(route('master-data.store', 'branch-admins'), [
                'branch_id' => $branch->id,
                'name' => 'Admin Banjarmasin',
                'email' => 'admin.bjm@example.test',
                'phone_number' => '08123456789',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('master-data.index', 'branch-admins'));

        $admin = User::where('email', 'admin.bjm@example.test')->firstOrFail();
        $this->assertTrue($admin->hasRole(UserRole::BranchAdmin->value));
        $this->assertSame($branch->id, $admin->branch_id);
    }

    public function test_branch_admin_is_scoped_to_its_own_branch_and_cannot_manage_branches(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'BRA', 'name' => 'Cabang A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'BRB', 'name' => 'Cabang B', 'city' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->post(route('master-data.store', 'pilgrims'), [
                'branch_id' => $branchB->id,
                'registration_number' => 'JMH-001',
                'full_name' => 'Jamaah Cabang A',
                'gender' => 'male',
                'status' => 'registered',
            ])
            ->assertRedirect(route('master-data.index', 'pilgrims'));

        $this->assertDatabaseHas('pilgrims', [
            'registration_number' => 'JMH-001',
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($admin)
            ->get(route('master-data.index', 'branches'))
            ->assertForbidden();
    }

    public function test_group_rejects_a_departure_from_another_branch(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'GRA', 'name' => 'Cabang A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'GRB', 'name' => 'Cabang B', 'city' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);
        $foreignDeparture = Departure::create([
            'branch_id' => $branchB->id,
            'code' => 'DEP-B',
            'program_name' => 'Program B',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->post(route('master-data.store', 'groups'), [
                'branch_id' => $branchB->id,
                'departure_id' => $foreignDeparture->id,
                'code' => 'GROUP-X',
                'name' => 'Group Silang',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('departure_id');

        $this->assertDatabaseMissing('groups', ['code' => 'GROUP-X']);
    }

    private function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);

        return $user;
    }
}
