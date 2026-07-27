<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\User;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoMasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_master_data_seeder_creates_operational_sample_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);

        $this->assertSame(2, Departure::query()->where('is_public', true)->where('status', 'scheduled')->count());
        $this->assertSame(2, Group::query()->count());
        $this->assertSame(30, Pilgrim::query()->count());
        $this->assertSame(26, Departure::query()->withCount('itineraries')->get()->sum('itineraries_count'));
        $this->assertGreaterThanOrEqual(11, Checkpoint::query()->where('is_active', true)->count());

        $package = Departure::query()
            ->with(['branch', 'hotels', 'itineraries', 'groups.tourLeader', 'groups.muthawwif'])
            ->where('code', 'DEP-RH-001')
            ->firstOrFail();

        $this->assertSame('Umroh Reguler Al Hijrah Januari 2027', $package->program_name);
        $this->assertSame(32_500_000, $package->price);
        $this->assertTrue($package->is_public);
        $this->assertSame('Cabang Banjarmasin', $package->branch->name);
        $this->assertCount(2, $package->hotels);
        $this->assertCount(13, $package->itineraries);
        $this->assertSame('Padil Banjarmasin', $package->groups->first()->tourLeader->full_name);
        $this->assertSame('Hafis Banjarmasin', $package->groups->first()->muthawwif->full_name);

        $this->assertDatabaseHas('checkpoints', [
            'name' => 'Pintu 79 Masjidil Haram',
            'category' => 'titik_kumpul',
            'departure_id' => $package->id,
            'group_id' => $package->groups->first()->id,
        ]);

        $this->assertTrue(User::query()->where('email', 'adminbjm@mantauumrah.id')->firstOrFail()->hasRole('admin-cabang'));
        $this->assertTrue(User::query()->where('email', 'padilbjm@mantauumrah.id')->firstOrFail()->hasRole('tour-leader'));
        $this->assertTrue(User::query()->where('email', 'hafisbjm@mantauumrah.id')->firstOrFail()->hasRole('muthawwif'));

        $this->assertDatabaseMissing('branches', ['code' => 'BJB']);
        $this->assertDatabaseMissing('branches', ['code' => 'MTP']);
    }
}
