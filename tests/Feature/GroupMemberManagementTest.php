<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Pilgrim;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_can_add_and_remove_pilgrims_from_its_group(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['pilgrim_ids' => [$pilgrim->id]])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $membership = GroupMember::whereBelongsTo($group)->whereBelongsTo($pilgrim)->firstOrFail();
        $this->assertSame('active', $membership->status);

        $this->actingAs($admin)
            ->delete(route('groups.members.destroy', [$group, $membership]))
            ->assertSessionHas('success');

        $membership->refresh();
        $this->assertSame('removed', $membership->status);
        $this->assertNotNull($membership->left_at);
    }

    public function test_pilgrim_cannot_join_two_active_groups_in_the_same_departure(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        $otherGroup = Group::create([
            'branch_id' => $group->branch_id,
            'departure_id' => $group->departure_id,
            'code' => 'GRP-OTHER',
            'name' => 'Group Lain',
        ]);
        GroupMember::create([
            'group_id' => $otherGroup->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['pilgrim_ids' => [$pilgrim->id]])
            ->assertSessionHasErrors('pilgrim_ids');

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
        ]);
    }

    public function test_branch_admin_cannot_open_another_branch_group(): void
    {
        [$admin] = $this->scenario();
        $foreignBranch = Branch::create(['code' => 'FOREIGN', 'name' => 'Cabang Lain', 'city' => 'Jakarta']);
        $departure = $this->departure($foreignBranch, 'DEP-FOREIGN');
        $foreignGroup = Group::create([
            'branch_id' => $foreignBranch->id,
            'departure_id' => $departure->id,
            'code' => 'GRP-FOREIGN',
            'name' => 'Group Rahasia',
        ]);

        $this->actingAs($admin)
            ->get(route('groups.members.index', $foreignGroup))
            ->assertForbidden();
    }

    public function test_group_capacity_cannot_be_exceeded(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        $group->update(['capacity' => 1]);
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $secondPilgrim = Pilgrim::create([
            'branch_id' => $group->branch_id,
            'registration_number' => 'JMH-BJM-002',
            'full_name' => 'Jamaah Kedua',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['pilgrim_ids' => [$secondPilgrim->id]])
            ->assertSessionHasErrors('pilgrim_ids');

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $secondPilgrim->id,
        ]);
    }

    /**
     * @return array{User, Group, Pilgrim}
     */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create(['code' => 'BJM', 'name' => 'Banjarmasin', 'city' => 'Banjarmasin']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);
        $departure = $this->departure($branch, 'DEP-BJM');
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'code' => 'GRP-BJM',
            'name' => 'Group Banjarmasin',
            'capacity' => 10,
        ]);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => 'JMH-BJM-001',
            'full_name' => 'Jamaah Banjarmasin',
            'gender' => 'male',
            'status' => 'active',
        ]);

        return [$admin, $group, $pilgrim];
    }

    private function departure(Branch $branch, string $code): Departure
    {
        return Departure::create([
            'branch_id' => $branch->id,
            'code' => $code,
            'program_name' => "Program {$branch->name}",
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(10),
        ]);
    }
}
