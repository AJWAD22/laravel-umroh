<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_national_statistics(): void
    {
        [$superAdmin] = $this->dashboardScenario();

        $response = $this->actingAs($superAdmin)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Dashboard Nasional')
            ->assertViewHas('scopeLabel', 'Nasional');

        $this->assertCardValue($response, 'Total Cabang', 2);
        $this->assertCardValue($response, 'Total Jamaah', 3);
    }

    public function test_branch_admin_dashboard_is_strictly_scoped_to_its_branch(): void
    {
        [, $branchAdmin] = $this->dashboardScenario();

        $response = $this->actingAs($branchAdmin)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Dashboard Cabang A')
            ->assertDontSee('Jamaah Rahasia Cabang B')
            ->assertViewHas('scopeLabel', 'Cabang A');

        $this->assertCardValue($response, 'Total Jamaah', 2);
        $response->assertViewHas('monitoring', fn (array $monitoring) => $monitoring === [
            'online' => 1,
            'offline' => 0,
            'unknown' => 0,
        ]);
    }

    private function assertCardValue(TestResponse $response, string $label, int $expected): void
    {
        $response->assertViewHas(
            'cards',
            fn (array $cards) => collect($cards)->contains(
                fn (array $card) => $card['label'] === $label && $card['value'] === $expected,
            ),
        );
    }

    /**
     * @return array{User, User}
     */
    private function dashboardScenario(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $branchA = Branch::create(['code' => 'CBA', 'name' => 'Cabang A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'CBB', 'name' => 'Cabang B', 'city' => 'Jakarta']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branchAdmin = User::factory()->create(['branch_id' => $branchA->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        $pilgrimA1 = $this->pilgrim($branchA, 'A-001', 'Jamaah Cabang A Satu');
        $this->pilgrim($branchA, 'A-002', 'Jamaah Cabang A Dua');
        $pilgrimB = $this->pilgrim($branchB, 'B-001', 'Jamaah Rahasia Cabang B');

        $groupA = $this->group($branchA, 'DEP-A', 'GRP-A');
        $groupB = $this->group($branchB, 'DEP-B', 'GRP-B');

        PilgrimLocation::create([
            'pilgrim_id' => $pilgrimA1->id,
            'group_id' => $groupA->id,
            'latitude' => 21.4224870,
            'longitude' => 39.8262060,
            'gps_status' => 'online',
            'recorded_at' => now(),
        ]);
        PilgrimLocation::create([
            'pilgrim_id' => $pilgrimB->id,
            'group_id' => $groupB->id,
            'latitude' => 21.4225000,
            'longitude' => 39.8262100,
            'gps_status' => 'offline',
            'recorded_at' => now(),
        ]);

        return [$superAdmin, $branchAdmin];
    }

    private function pilgrim(Branch $branch, string $number, string $name): Pilgrim
    {
        return Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => $number,
            'full_name' => $name,
            'gender' => 'male',
        ]);
    }

    private function group(Branch $branch, string $departureCode, string $groupCode): Group
    {
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => $departureCode,
            'program_name' => "Program {$branch->name}",
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(10),
            'status' => 'scheduled',
        ]);

        return Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'code' => $groupCode,
            'name' => "Group {$branch->name}",
        ]);
    }

}
