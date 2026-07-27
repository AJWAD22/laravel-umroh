<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Group;
use App\Models\LocationHistory;
use App\Models\Pilgrim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_cleanup_removes_only_expired_histories(): void
    {
        $branch = Branch::create(['code' => 'CLN', 'name' => 'Cabang Cleanup', 'city' => 'Banjarmasin']);
        $group = Group::create(['branch_id' => $branch->id, 'code' => 'CLN-GRP', 'name' => 'Rombongan Cleanup']);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => 'CLN-JMH-001',
            'full_name' => 'Jamaah Cleanup',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $expired = LocationHistory::create([
            'branch_id' => $branch->id,
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'recorded_at' => now()->subDays(91),
            'device_recorded_at' => now()->subDays(91),
            'server_received_at' => now()->subDays(91),
        ]);

        $fresh = LocationHistory::create([
            'branch_id' => $branch->id,
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'latitude' => 21.422588,
            'longitude' => 39.826307,
            'recorded_at' => now()->subDays(10),
            'device_recorded_at' => now()->subDays(10),
            'server_received_at' => now()->subDays(10),
        ]);

        $this->artisan('location:cleanup --days=90')
            ->expectsOutputToContain('dihapus: 1')
            ->assertSuccessful();

        $this->assertDatabaseMissing('location_histories', ['id' => $expired->id]);
        $this->assertDatabaseHas('location_histories', ['id' => $fresh->id]);
    }
}
