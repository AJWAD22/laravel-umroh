<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\PilgrimRegistration;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\PublicPackageDemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPackageRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_verified_travel_profile_and_demo_packages(): void
    {
        SystemSetting::query()->where('key', 'company_name')->update(['value' => 'PT Travel Amanah']);
        SystemSetting::query()->where('key', 'company_tagline')->update(['value' => 'Melayani perjalanan ibadah dengan sepenuh hati.']);
        SystemSetting::query()->where('key', 'company_license')->update(['value' => 'PPIU-TERVERIFIKASI']);

        $this->seed(PublicPackageDemoSeeder::class);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('PT Travel Amanah')
            ->assertSee('Melayani perjalanan ibadah dengan sepenuh hati.')
            ->assertSee('PPIU-TERVERIFIKASI')
            ->assertSee('Umroh Hemat 9 Hari')
            ->assertSee('Umroh Reguler 12 Hari')
            ->assertSee('Umroh Plus Thaif 12 Hari');

        $this->assertDatabaseCount('departures', 3);
        $this->assertDatabaseCount('departure_itineraries', 20);
    }

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

        $this->post(route('public-registration.biodata.store'), [
            'full_name' => 'Jamaah Publik',
            'nik' => '6371010101010001',
            'gender' => 'male',
            'phone' => '081234567890',
            'birth_date' => '1990-01-01',
            'address' => 'Banjarmasin',
        ])->assertRedirect(route('public-registration.packages'));

        $this->get(route('public-registration.packages'))
            ->assertOk()
            ->assertSee('Jamaah Publik')
            ->assertSee('Umroh Publik 12 Hari');

        $this->post(route('public-registration.complete'), [
            'departure_id' => $departure->id,
        ])->assertRedirect(route('packages.show', $departure));

        $this->assertDatabaseHas('pilgrim_registrations', [
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Publik',
            'status' => 'submitted',
        ]);
    }

    public function test_duplicate_registration_and_registration_over_quota_are_rejected(): void
    {
        $branch = Branch::create(['code' => 'QTA', 'name' => 'Cabang Kuota', 'city' => 'Banjarmasin']);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'QTA-DEP-001',
            'program_name' => 'Umroh Kuota Satu',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(9),
            'quota' => 1,
            'status' => 'scheduled',
            'is_public' => true,
        ]);

        $payload = [
            'full_name' => 'Jamaah Pertama',
            'nik' => '6371010101010001',
            'gender' => 'male',
            'phone' => '081111111111',
            'birth_date' => '1990-01-01',
            'address' => 'Banjarmasin',
        ];
        $this->post(route('public-registration.biodata.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('public-registration.complete'), ['departure_id' => $departure->id])
            ->assertSessionHasNoErrors();

        $this->post(route('public-registration.biodata.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('public-registration.complete'), ['departure_id' => $departure->id])
            ->assertSessionHasErrors('departure_id');

        $this->post(route('public-registration.biodata.store'), [
            ...$payload,
            'full_name' => 'Jamaah Kedua',
            'nik' => '6371010101010002',
            'phone' => '082222222222',
        ])->assertSessionHasNoErrors();
        $this->post(route('public-registration.complete'), ['departure_id' => $departure->id])
            ->assertSessionHasErrors('departure_id');

        $this->assertDatabaseCount('pilgrim_registrations', 1);
    }

    public function test_branch_admin_can_manage_only_its_registration_status(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create(['code' => 'REG', 'name' => 'Cabang Registrasi', 'city' => 'Banjarmasin']);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'REG-DEP-001',
            'program_name' => 'Paket Registrasi',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(9),
            'status' => 'scheduled',
        ]);
        $registration = PilgrimRegistration::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Calon Jamaah',
            'gender' => 'female',
            'phone' => '083333333333',
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->get(route('registrations.index'))
            ->assertOk()
            ->assertSee('Calon Jamaah');

        $this->patch(route('registrations.update', $registration), ['status' => 'contacted'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pilgrim_registrations', [
            'id' => $registration->id,
            'status' => 'contacted',
        ]);
    }
}
