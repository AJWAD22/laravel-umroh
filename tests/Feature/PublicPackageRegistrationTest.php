<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\PilgrimRegistration;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\PublicPackageDemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPackageRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_travel_profile_without_exposing_package_details(): void
    {
        $this->seed(SystemSettingSeeder::class);
        SystemSetting::query()->where('key', 'company_name')->update(['value' => 'PT Travel Amanah']);
        SystemSetting::query()->where('key', 'company_tagline')->update(['value' => 'Melayani perjalanan ibadah dengan sepenuh hati.']);
        SystemSetting::query()->where('key', 'company_license')->update(['value' => 'PPIU-TERVERIFIKASI']);

        $this->seed(PublicPackageDemoSeeder::class);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('PT Travel Amanah')
            ->assertSee('Melayani perjalanan ibadah dengan sepenuh hati.')
            ->assertSee('PPIU-TERVERIFIKASI')
            ->assertSee('Buat Akun Jamaah')
            ->assertDontSee('Umroh Hemat 9 Hari')
            ->assertDontSee('Rp 28.900.000');

        $this->assertDatabaseCount('departures', 3);
        $this->assertDatabaseCount('departure_itineraries', 20);
    }

    public function test_jamaah_creates_account_selects_package_then_submits_biodata(): void
    {
        $this->seed(RolePermissionSeeder::class);
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

        $this->post(route('portal.register.store'), [
            'name' => 'Jamaah Publik',
            'phone' => '081234567890',
            'email' => 'jamaah@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            'terms' => '1',
        ])->assertRedirect(route('portal.packages.index'));

        $user = User::query()->where('name', 'Jamaah Publik')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('pilgrim_portal_accounts', [
            'user_id' => $user->id,
            'phone' => '6281234567890',
        ]);

        $this->get(route('portal.packages.index'))
            ->assertOk()
            ->assertSee('Umroh Publik 12 Hari');

        $this->post(route('portal.packages.select', $departure))
            ->assertRedirect(route('portal.biodata.edit'));

        $this->get(route('portal.biodata.edit'))
            ->assertOk()
            ->assertSee('Umroh Publik 12 Hari')
            ->assertSee('Lengkapi Biodata Jamaah');

        $this->post(route('portal.biodata.store'), [
            'full_name' => 'Jamaah Publik',
            'nik' => '6371010101010001',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'passport_number' => 'A1234567',
            'passport_expired_at' => today()->addYears(2)->toDateString(),
            'address' => 'Banjarmasin',
            'emergency_contact_name' => 'Keluarga Jamaah',
            'emergency_contact_phone' => '081200000000',
            'confirmation' => '1',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('pilgrim_registrations', [
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Publik',
            'status' => 'submitted',
            'payment_status' => 'pending_branch_payment',
        ]);
    }

    public function test_package_quota_is_checked_again_when_biodata_is_submitted(): void
    {
        $this->seed(RolePermissionSeeder::class);
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

        PilgrimRegistration::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Pengisi Kuota',
            'gender' => 'male',
            'phone' => '081111111111',
        ]);
        $user = User::factory()->create(['name' => 'Jamaah Kedua']);
        \App\Models\PilgrimPortalAccount::create(['user_id' => $user->id, 'phone' => '6282222222222']);
        $user->assignRole('jamaah');
        $this->actingAs($user)->post(route('portal.packages.select', $departure));

        $this->post(route('portal.biodata.store'), [
            'full_name' => 'Jamaah Kedua',
            'nik' => '6371010101010002',
            'gender' => 'female',
            'birth_date' => '1992-01-01',
            'address' => 'Banjarmasin',
            'emergency_contact_name' => 'Keluarga',
            'emergency_contact_phone' => '081200000000',
            'confirmation' => '1',
        ])->assertSessionHasErrors('confirmation');

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

        $this->patch(route('registrations.update', $registration), [
            'status' => 'contacted',
            'payment_status' => 'verified',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pilgrim_registrations', [
            'id' => $registration->id,
            'status' => 'contacted',
            'payment_status' => 'verified',
        ]);
    }

    public function test_verified_registration_becomes_operational_pilgrim_in_selected_group(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create(['code' => 'OPS', 'name' => 'Cabang Operasional', 'city' => 'Banjarmasin']);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'OPS-DEP-001',
            'program_name' => 'Paket Operasional',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(9),
            'status' => 'scheduled',
        ]);
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'code' => 'OPS-GRP-001',
            'name' => 'Rombongan Operasional',
            'capacity' => 45,
        ]);
        $portalUser = User::factory()->create(['branch_id' => null, 'name' => 'Pendaftar Portal']);
        \App\Models\PilgrimPortalAccount::create([
            'user_id' => $portalUser->id,
            'phone' => '6281234500000',
        ]);
        $portalUser->assignRole('jamaah');
        $registration = PilgrimRegistration::create([
            'user_id' => $portalUser->id,
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Pendaftar Portal',
            'nik' => '6371010101010099',
            'gender' => 'male',
            'phone' => '6281234500000',
            'birth_date' => '1990-01-01',
            'address' => 'Banjarmasin',
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->patch(route('registrations.update', $registration), [
                'status' => 'approved',
                'payment_status' => 'verified',
                'group_id' => $group->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $pilgrim = Pilgrim::query()->where('user_id', $portalUser->id)->firstOrFail();
        $this->assertSame($branch->id, $portalUser->fresh()->branch_id);
        $this->assertMatchesRegularExpression('/^OPS-JMH-\d{5}$/', $pilgrim->registration_number);
        $this->assertNotNull($pilgrim->activation_pin_hash);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
        ]);
    }
}
