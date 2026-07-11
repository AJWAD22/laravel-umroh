<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_open_live_map(): void
    {
        [$superAdmin] = $this->users();

        $this->actingAs($superAdmin)
            ->get(route('monitoring.map.index'))
            ->assertOk()
            ->assertSee('Monitoring Jamaah')
            ->assertSee('Data GPS Langsung')
            ->assertSee('monitoring-detail', false);
    }

    public function test_super_admin_can_filter_real_markers_by_branch_and_status(): void
    {
        [$superAdmin, , $branchB] = $this->users();

        $response = $this->actingAs($superAdmin)->getJson(route('monitoring.map.data', [
            'branch_id' => $branchB->id,
            'status' => 'online',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('source', 'database')
            ->assertJsonPath('summary.offline', 0)
            ->assertJsonStructure([
                'markers' => [
                    '*' => [
                        'id',
                        'type',
                        'name',
                        'registration_number',
                        'photo_url',
                        'phone',
                        'branch',
                        'group',
                        'tour_leader',
                        'muthawwif',
                        'location_name',
                        'latitude',
                        'longitude',
                        'accuracy',
                        'battery',
                        'status',
                        'updated_at',
                    ],
                ],
            ]);

        $markers = collect($response->json('markers'));
        $this->assertNotEmpty($markers);
        $this->assertTrue($markers->every(
            fn (array $marker) => $marker['branch_id'] === $branchB->id
                && $marker['status'] === 'online',
        ));
    }

    public function test_branch_admin_cannot_override_its_branch_scope(): void
    {
        [, $branchAdmin, $branchB] = $this->users();

        $response = $this->actingAs($branchAdmin)->getJson(route('monitoring.map.data', [
            'branch_id' => $branchB->id,
        ]));

        $response->assertOk();
        $markers = collect($response->json('markers'));

        $this->assertNotEmpty($markers);
        $this->assertTrue($markers->every(
            fn (array $marker) => $marker['branch_id'] === $branchAdmin->branch_id,
        ));
    }

    /**
     * @return array{User, User, Branch}
     */
    private function users(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'MAP-A', 'name' => 'Cabang Map A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'MAP-B', 'name' => 'Cabang Map B', 'city' => 'Jakarta']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $branchAdmin = User::factory()->create(['branch_id' => $branchA->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        foreach ([$branchA, $branchB] as $index => $branch) {
            $pilgrim = Pilgrim::create([
                'branch_id' => $branch->id,
                'registration_number' => "MAP-{$branch->id}",
                'full_name' => "Jamaah Map {$branch->id}",
                'gender' => 'male',
                'status' => 'active',
            ]);
            PilgrimLocation::create([
                'pilgrim_id' => $pilgrim->id,
                'latitude' => 21.422487 + ($index * 0.001),
                'longitude' => 39.826206 + ($index * 0.001),
                'accuracy' => 5,
                'battery_level' => 80,
                'gps_status' => 'online',
                'recorded_at' => now(),
            ]);
        }

        return [$superAdmin, $branchAdmin, $branchB];
    }
}
