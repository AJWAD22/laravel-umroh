<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Departure;
use Database\Seeders\OperationalPackageExampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalPackageExampleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_package_example_seeder_creates_a_complete_public_package(): void
    {
        $this->seed(OperationalPackageExampleSeeder::class);

        $package = Departure::query()
            ->with(['branch', 'hotels', 'groups.tourLeader', 'groups.muthawwif', 'itineraries'])
            ->where('code', 'BJM-REG-12H-001')
            ->firstOrFail();

        $this->assertSame('Cabang Banjarmasin', $package->branch->name);
        $this->assertSame('Umroh Reguler 12 Hari', $package->program_name);
        $this->assertTrue($package->is_public);
        $this->assertSame('scheduled', $package->status);
        $this->assertCount(2, $package->hotels);
        $this->assertCount(12, $package->itineraries);
        $this->assertCount(1, $package->groups);
        $this->assertNotNull($package->groups->first()->tourLeader);
        $this->assertNotNull($package->groups->first()->muthawwif);
        $this->assertSame(4, Checkpoint::query()->where('departure_id', $package->id)->count());

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Umroh Reguler 12 Hari')
            ->assertSee('Rp32.500.000')
            ->assertSee('Al Safwah Royale Orchid Makkah')
            ->assertSee('Lihat Detail');
    }
}
