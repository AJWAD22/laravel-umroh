<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Departure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPackageRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_shows_scheduled_packages_and_accepts_registration(): void
    {
        $branch = Branch::create(['code' => 'PUB', 'name' => 'Cabang Publik', 'city' => 'Makassar']);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'PUB-DEP-2026-001',
            'program_name' => 'Umroh Publik 12 Hari',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(11),
            'status' => 'scheduled',
            'is_public' => true,
        ]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Umroh Publik 12 Hari');

        $this->post(route('public-registration.store'), [
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Publik',
            'gender' => 'male',
            'phone' => '081234567890',
        ])->assertRedirect(route('packages.show', $departure));

        $this->assertDatabaseHas('pilgrim_registrations', [
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Publik',
            'status' => 'submitted',
        ]);
    }
}
