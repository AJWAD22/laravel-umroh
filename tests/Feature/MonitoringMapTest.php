<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Checkpoint;
use App\Models\Departure;
use App\Models\Group;
use App\Models\MobileDevice;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\StaffLocation;
use App\Models\TourLeader;
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
            ->assertSee('Monitoring Perjalanan')
            ->assertSee('Pembaruan otomatis')
            ->assertSee('monitoring-detail', false)
            ->assertSee('monitoring-list', false)
            ->assertSee('monitoring-departure', false);
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

    public function test_sos_filter_and_operational_layers_are_available_for_selected_group(): void
    {
        [, $branchAdmin, $branchB] = $this->users();
        $branch = $branchAdmin->branch;
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'MAP-DEP-A',
            'program_name' => 'Perjalanan Monitoring A',
            'departure_date' => today()->addDay(),
            'return_date' => today()->addDays(10),
            'status' => 'scheduled',
        ]);
        $staffUser = User::factory()->create(['branch_id' => $branch->id]);
        $tourLeader = TourLeader::create([
            'branch_id' => $branch->id,
            'user_id' => $staffUser->id,
            'employee_number' => 'TL-MAP-001',
            'full_name' => 'Petugas Monitoring',
            'is_active' => true,
        ]);
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'tour_leader_id' => $tourLeader->id,
            'code' => 'GRP-MAP-A',
            'name' => 'Rombongan Monitoring A',
            'is_active' => true,
        ]);
        StaffLocation::create([
            'user_id' => $staffUser->id,
            'branch_id' => $branch->id,
            'role' => 'tour-leader',
            'latitude' => 21.423,
            'longitude' => 39.827,
            'accuracy' => 5,
            'recorded_at' => now(),
        ]);
        Checkpoint::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'name' => 'Titik Jadwal Perjalanan A',
            'category' => 'transportasi',
            'city' => 'jeddah',
            'latitude' => 21.426,
            'longitude' => 39.830,
            'geofence_radius_meters' => 200,
            'is_active' => true,
        ]);
        Checkpoint::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'group_id' => $group->id,
            'name' => 'Titik Kumpul Rombongan A',
            'category' => 'titik_kumpul',
            'city' => 'makkah',
            'latitude' => 21.424,
            'longitude' => 39.828,
            'geofence_radius_meters' => 150,
            'is_active' => true,
        ]);
        Checkpoint::create([
            'branch_id' => $branchB->id,
            'name' => 'Titik Cabang Lain',
            'category' => 'titik_kumpul',
            'city' => 'makkah',
            'latitude' => 21.425,
            'longitude' => 39.829,
            'is_active' => true,
        ]);

        $response = $this->actingAs($branchAdmin)->getJson(route('monitoring.map.data', [
            'departure_id' => $departure->id,
            'group_id' => $group->id,
            'status' => 'sos',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('summary.staff', 1)
            ->assertJsonPath('summary.checkpoints', 2)
            ->assertJsonPath('staff.0.type', 'tour-leader')
            ->assertJsonFragment(['name' => 'Titik Jadwal Perjalanan A'])
            ->assertJsonFragment(['name' => 'Titik Kumpul Rombongan A'])
            ->assertJsonMissing(['name' => 'Titik Cabang Lain']);
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
            $pilgrimUser = User::factory()->create(['branch_id' => $branch->id]);
            $pilgrim = Pilgrim::create([
                'branch_id' => $branch->id,
                'user_id' => $pilgrimUser->id,
                'registration_number' => "MAP-{$branch->id}",
                'full_name' => "Jamaah Map {$branch->id}",
                'gender' => 'male',
                'status' => 'active',
            ]);
            MobileDevice::create([
                'user_id' => $pilgrimUser->id,
                'device_uuid' => "map-device-{$branch->id}",
                'device_name' => "HP Jamaah Map {$branch->id}",
                'platform' => 'android',
                'activated_at' => now(),
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
