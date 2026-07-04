<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\LocationHistory;
use App\Models\Pilgrim;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_points_are_returned_without_a_distance_limit(): void
    {
        [$admin, $pilgrim] = $this->scenario();
        $date = today();
        $coordinates = [
            [21.4224870, 39.8262060],
            [24.4672000, 39.6111000],
            [21.5433330, 39.1727790],
        ];

        foreach ($coordinates as $index => [$latitude, $longitude]) {
            LocationHistory::create([
                'pilgrim_id' => $pilgrim->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => 5,
                'battery_level' => 90 - ($index * 10),
                'recorded_at' => $date->copy()->setTime(8 + $index, 0),
            ]);
        }

        $response = $this->actingAs($admin)->getJson(route('monitoring.tracking.data', [
            'pilgrim_id' => $pilgrim->id,
            'date' => $date->toDateString(),
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('source', 'database')
            ->assertJsonPath('summary.total_points', 3)
            ->assertJsonCount(3, 'points')
            ->assertJsonPath('points.1.latitude', 24.4672);

        $this->assertGreaterThan(300, $response->json('summary.total_distance_km'));
    }

    public function test_branch_admin_cannot_request_another_branch_pilgrim_history(): void
    {
        [$admin] = $this->scenario();
        $foreignBranch = Branch::create(['code' => 'TRK-B', 'name' => 'Cabang B', 'city' => 'Jakarta']);
        $foreignPilgrim = Pilgrim::create([
            'branch_id' => $foreignBranch->id,
            'registration_number' => 'FOREIGN-001',
            'full_name' => 'Jamaah Cabang Lain',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->getJson(route('monitoring.tracking.data', [
            'pilgrim_id' => $foreignPilgrim->id,
            'date' => today()->toDateString(),
        ]))->assertForbidden();
    }

    public function test_missing_history_returns_an_empty_real_dataset(): void
    {
        [$admin, $pilgrim] = $this->scenario();

        $this->actingAs($admin)->getJson(route('monitoring.tracking.data', [
            'pilgrim_id' => $pilgrim->id,
            'date' => today()->subYear()->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonPath('source', 'database')
            ->assertJsonPath('summary.total_points', 0)
            ->assertJsonCount(0, 'points');
    }

    public function test_tracking_page_only_lists_pilgrims_in_the_admin_branch(): void
    {
        [$admin, $pilgrim] = $this->scenario();

        $this->actingAs($admin)
            ->get(route('monitoring.tracking.index'))
            ->assertOk()
            ->assertSee($pilgrim->full_name)
            ->assertSee('tanpa batas jarak');
    }

    /**
     * @return array{User, Pilgrim}
     */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::firstOrCreate(
            ['code' => 'TRK-A'],
            ['name' => 'Cabang Tracking', 'city' => 'Makassar'],
        );
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);
        $pilgrim = Pilgrim::firstOrCreate(
            ['registration_number' => 'TRACK-001'],
            [
                'branch_id' => $branch->id,
                'full_name' => 'Jamaah Tracking',
                'gender' => 'male',
                'status' => 'active',
            ],
        );

        return [$admin, $pilgrim];
    }
}
