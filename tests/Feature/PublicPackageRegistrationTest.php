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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPackageRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_travel_profile_and_public_package_summary(): void
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
            ->assertSee('Paket Keberangkatan')
            ->assertSee('Umroh Hemat 9 Hari')
            ->assertSee('Rp28.900.000')
            ->assertSee('Lihat Detail')
            ->assertDontSee('Lengkapi Biodata Jamaah');

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
            'payment_status' => 'unpaid',
        ]);
        $registration = PilgrimRegistration::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNotSame('6371010101010001', $registration->getRawOriginal('nik'));
        $this->assertSame('6371010101010001', $registration->nik);
        $this->assertSame('************0001', $registration->maskedNik());

        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);
        $this->actingAs($admin)
            ->get(route('registrations.index'))
            ->assertOk()
            ->assertSee('************0001')
            ->assertDontSee('6371010101010001');
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
            'status' => 'submitted',
            'payment_status' => 'unpaid',
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

        $this->assertDatabaseCount('pilgrim_registrations', 2);
        $this->assertDatabaseHas('pilgrim_registrations', [
            'user_id' => $user->id,
            'departure_id' => $departure->id,
            'status' => 'draft',
        ]);
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
            'status' => 'submitted',
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->get(route('registrations.index'))
            ->assertOk()
            ->assertSee('Calon Jamaah');

        $this->patch(route('registrations.update', $registration), [
            'status' => 'revision_requested',
            'payment_status' => 'unpaid',
            'revision_notes' => 'Mohon lengkapi paspor.',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pilgrim_registrations', [
            'id' => $registration->id,
            'status' => 'revision_requested',
            'payment_status' => 'unpaid',
            'revision_notes' => 'Mohon lengkapi paspor.',
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
            'emergency_contact_name' => 'Keluarga Portal',
            'emergency_contact_phone' => '081200000099',
            'status' => 'submitted',
            'payment_status' => 'unpaid',
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->patch(route('registrations.update', $registration), [
                'status' => 'approved',
                'payment_status' => 'pending_branch_payment',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('registrations.update', $registration->fresh()), [
                'status' => 'in_group',
                'payment_status' => 'paid',
                'group_id' => $group->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $pilgrim = Pilgrim::query()->where('user_id', $portalUser->id)->firstOrFail();
        $this->assertSame($branch->id, $portalUser->fresh()->branch_id);
        $this->assertMatchesRegularExpression('/^OPS-JMH-\d{5}$/', $pilgrim->registration_number);
        $this->assertNull($pilgrim->activation_pin_hash);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
        ]);
    }

    public function test_complete_branch_package_registration_revision_payment_and_group_flow(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
        $superAdmin = User::factory()->create(['branch_id' => null]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->post(route('master-data.store', 'branches'), [
                'code' => 'SIM',
                'name' => 'Cabang Simulasi',
                'city' => 'Makassar',
                'province' => 'Sulawesi Selatan',
                'is_active' => '1',
            ])
            ->assertRedirect(route('master-data.index', 'branches'));
        $branch = Branch::where('code', 'SIM')->firstOrFail();

        $this->actingAs($superAdmin)
            ->post(route('master-data.store', 'branch-admins'), [
                'branch_id' => $branch->id,
                'name' => 'Admin Simulasi',
                'email' => 'admin.simulasi@example.test',
                'phone_number' => '081234500000',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('master-data.index', 'branch-admins'));
        $admin = User::where('email', 'admin.simulasi@example.test')->firstOrFail();

        foreach ([['Hotel Makkah Simulasi', 'makkah'], ['Hotel Madinah Simulasi', 'madinah']] as [$name, $city]) {
            $this->actingAs($admin)
                ->post(route('master-data.store', 'hotels'), [
                    'name' => $name,
                    'city' => $city,
                    'address' => "Alamat {$name}",
                    'geofence_radius_meters' => 250,
                    'is_active' => '1',
                ])
                ->assertSessionHasNoErrors();
        }
        $hotelIds = \App\Models\Hotel::where('branch_id', $branch->id)->pluck('id')->all();

        $this->actingAs($admin)
            ->post(route('master-data.store', 'departures'), [
                'program_name' => 'Umroh Simulasi 10 Hari',
                'description' => 'Paket simulasi lengkap.',
                'facilities' => "Visa umroh\nHotel sesuai paket\nPendamping perjalanan",
                'requirements' => "KTP\nPaspor\nFoto jamaah",
                'departure_date' => today()->addMonth()->toDateString(),
                'return_date' => today()->addMonth()->addDays(9)->toDateString(),
                'departure_airport' => 'UPG',
                'arrival_airport' => 'JED',
                'airline' => 'Garuda Indonesia',
                'flight_number' => 'GA-980',
                'price' => 32500000,
                'quota' => 2,
                'hotel_ids' => $hotelIds,
                'itinerary_plan' => "1|Berangkat|Makassar|Penerbangan menuju Jeddah\n2|Umroh pertama|Makkah|Thawaf, sai, dan tahallul",
                'is_public' => '1',
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('master-data.index', 'departures'))
            ->assertSessionHasNoErrors();
        $departure = Departure::where('program_name', 'Umroh Simulasi 10 Hari')->firstOrFail();

        $this->post('/logout');

        $this->post(route('portal.register.store'), [
            'name' => 'Jamaah Simulasi',
            'phone' => '081299900001',
            'email' => 'jamaah.simulasi@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ])->assertRedirect(route('portal.packages.index'));
        $jamaah = User::where('email', 'jamaah.simulasi@example.test')->firstOrFail();

        $this->actingAs($jamaah)
            ->get(route('portal.packages.show', $departure))
            ->assertOk()
            ->assertSee('Umroh Simulasi 10 Hari')
            ->assertSee('Visa umroh')
            ->assertSee('KTP');

        $this->actingAs($jamaah)
            ->post(route('portal.packages.select', $departure))
            ->assertRedirect(route('portal.biodata.edit'));
        $registration = PilgrimRegistration::where('user_id', $jamaah->id)->firstOrFail();
        $this->assertSame('draft', $registration->status);

        $this->actingAs($jamaah)
            ->post(route('portal.biodata.store'), [
                'action' => 'submit',
                'full_name' => 'Jamaah Simulasi',
                'nik' => '7371010101010001',
                'gender' => 'male',
                'birth_date' => '1990-01-01',
                'passport_number' => 'X1234567',
                'passport_expired_at' => today()->addYears(2)->toDateString(),
                'address' => 'Makassar',
                'emergency_contact_name' => 'Keluarga Simulasi',
                'emergency_contact_phone' => '081299900002',
                'health_notes' => 'Tidak ada catatan khusus.',
                'photo' => UploadedFile::fake()->image('jamaah.jpg'),
                'identity_document' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
                'passport_document' => UploadedFile::fake()->create('paspor.pdf', 100, 'application/pdf'),
                'confirmation' => '1',
            ])
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHasNoErrors();
        $registration->refresh();
        $this->assertSame('submitted', $registration->status);
        $this->assertNotNull($registration->identity_document_path);

        $this->actingAs($admin)
            ->patch(route('registrations.update', $registration), [
                'status' => 'revision_requested',
                'payment_status' => 'unpaid',
                'revision_notes' => 'Mohon perbaiki catatan dokumen.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($jamaah)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Mohon perbaiki catatan dokumen.');

        $this->actingAs($jamaah)
            ->post(route('portal.biodata.store'), [
                'action' => 'submit',
                'full_name' => 'Jamaah Simulasi',
                'nik' => '7371010101010001',
                'gender' => 'male',
                'birth_date' => '1990-01-01',
                'passport_number' => 'X1234567',
                'passport_expired_at' => today()->addYears(2)->toDateString(),
                'address' => 'Makassar',
                'emergency_contact_name' => 'Keluarga Simulasi',
                'emergency_contact_phone' => '081299900002',
                'health_notes' => 'Tidak ada catatan khusus.',
                'document_notes' => 'Dokumen sudah diperbaiki.',
                'confirmation' => '1',
            ])
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('registrations.update', $registration->fresh()), [
                'status' => 'approved',
                'payment_status' => 'pending_branch_payment',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('master-data.store', 'groups'), [
                'departure_id' => $departure->id,
                'name' => 'Rombongan Simulasi',
                'capacity' => 2,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();
        $group = Group::where('name', 'Rombongan Simulasi')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('registrations.update', $registration->fresh()), [
                'status' => 'in_group',
                'payment_status' => 'paid',
                'group_id' => $group->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($jamaah)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Masuk Rombongan')
            ->assertSee('Lunas');

        $pilgrim = Pilgrim::where('user_id', $jamaah->id)->firstOrFail();
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
        ]);
    }
}
