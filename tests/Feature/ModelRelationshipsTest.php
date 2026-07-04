<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Hotel;
use App\Models\LocationHistory;
use App\Models\Muthawwif;
use App\Models\Notification;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\SosReport;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_relationships_can_be_loaded_in_both_directions(): void
    {
        $branch = Branch::create([
            'code' => 'BJM',
            'name' => 'Cabang Banjarmasin',
            'city' => 'Banjarmasin',
        ]);

        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $pilgrimUser = User::factory()->create(['branch_id' => $branch->id]);

        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'user_id' => $pilgrimUser->id,
            'registration_number' => 'JMH-001',
            'full_name' => 'Jamaah Satu',
            'gender' => 'male',
        ]);

        $tourLeader = TourLeader::create([
            'branch_id' => $branch->id,
            'employee_number' => 'TL-001',
            'full_name' => 'Tour Leader Satu',
        ]);

        $muthawwif = Muthawwif::create([
            'branch_id' => $branch->id,
            'employee_number' => 'MTF-001',
            'full_name' => 'Muthawwif Satu',
        ]);

        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'DEP-001',
            'program_name' => 'Umrah Reguler',
            'departure_date' => '2026-08-01',
            'return_date' => '2026-08-12',
        ]);

        $hotel = Hotel::create([
            'branch_id' => $branch->id,
            'name' => 'Hotel Makkah',
            'city' => 'makkah',
        ]);

        $departure->hotels()->attach($hotel, [
            'sequence' => 1,
            'check_in_at' => '2026-08-02',
            'check_out_at' => '2026-08-07',
        ]);

        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'tour_leader_id' => $tourLeader->id,
            'muthawwif_id' => $muthawwif->id,
            'code' => 'GRP-001',
            'name' => 'Group Satu',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'joined_at' => now(),
        ]);

        $this->assertTrue($branch->pilgrims->contains($pilgrim));
        $this->assertTrue($pilgrimUser->pilgrim->is($pilgrim));
        $this->assertTrue($departure->hotels->contains($hotel));
        $this->assertTrue($hotel->departures->contains($departure));
        $this->assertTrue($group->tourLeader->is($tourLeader));
        $this->assertTrue($group->muthawwif->is($muthawwif));
        $this->assertTrue($group->pilgrims->contains($pilgrim));
        $this->assertTrue($pilgrim->groups->contains($group));
        $this->assertTrue($admin->branch->is($branch));
    }

    public function test_tracking_sos_and_notification_relationships_are_consistent(): void
    {
        [$branch, $admin, $pilgrim, $group] = $this->createMonitoringContext();

        $latest = PilgrimLocation::create([
            'pilgrim_id' => $pilgrim->id,
            'group_id' => $group->id,
            'latitude' => 21.4224870,
            'longitude' => 39.8262060,
            'battery_level' => 85,
            'gps_status' => 'online',
            'recorded_at' => now(),
        ]);

        $history = LocationHistory::create([
            'pilgrim_id' => $pilgrim->id,
            'group_id' => $group->id,
            'latitude' => 21.4224870,
            'longitude' => 39.8262060,
            'recorded_at' => now(),
        ]);

        $sos = SosReport::create([
            'branch_id' => $branch->id,
            'pilgrim_id' => $pilgrim->id,
            'group_id' => $group->id,
            'handled_by' => $admin->id,
            'latitude' => 21.4224870,
            'longitude' => 39.8262060,
            'reported_at' => now(),
        ]);

        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'type' => 'sos',
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'data' => ['sos_report_id' => $sos->id],
        ]);

        $this->assertTrue($pilgrim->latestLocation->is($latest));
        $this->assertTrue($pilgrim->locationHistories->contains($history));
        $this->assertTrue($group->sosReports->contains($sos));
        $this->assertTrue($admin->handledSosReports->contains($sos));
        $this->assertTrue($notification->notifiable->is($admin));
        $this->assertTrue($branch->notifications->contains($notification));
        $this->assertSame('85', (string) $latest->battery_level);
        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $history->recorded_at);
    }

    /**
     * @return array{Branch, User, Pilgrim, Group}
     */
    private function createMonitoringContext(): array
    {
        $branch = Branch::create([
            'code' => 'MKS',
            'name' => 'Cabang Makassar',
            'city' => 'Makassar',
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => 'JMH-MKS-001',
            'full_name' => 'Jamaah Makassar',
            'gender' => 'female',
        ]);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'DEP-MKS-001',
            'program_name' => 'Umrah Makassar',
            'departure_date' => '2026-09-01',
            'return_date' => '2026-09-12',
        ]);
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'code' => 'GRP-MKS-001',
            'name' => 'Group Makassar',
        ]);

        return [$branch, $admin, $pilgrim, $group];
    }
}
