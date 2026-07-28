<?php

namespace Tests\Feature;

use App\Enums\MobileRole;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_manages_foundation_and_views_operational_data_only(): void
    {
        $superAdmin = $this->superAdmin();

        foreach (['branches', 'branch-admins'] as $resource) {
            $this->actingAs($superAdmin)
                ->get(route('master-data.index', $resource))
                ->assertOk();
        }

        foreach (['pilgrims', 'tour-leaders', 'muthawwifs', 'groups', 'hotels', 'departures', 'checkpoints'] as $resource) {
            $this->actingAs($superAdmin)
                ->get(route('master-data.index', $resource))
                ->assertOk();

            $this->actingAs($superAdmin)
                ->get(route('master-data.create', $resource))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_create_a_branch_admin_with_the_correct_role(): void
    {
        $superAdmin = $this->superAdmin();
        $branch = Branch::create(['code' => 'BJM', 'name' => 'Banjarmasin', 'city' => 'Banjarmasin']);

        $this->actingAs($superAdmin)
            ->post(route('master-data.store', 'branch-admins'), [
                'branch_id' => $branch->id,
                'name' => 'Admin Banjarmasin',
                'email' => 'admin.bjm@example.test',
                'phone_number' => '08123456789',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('master-data.index', 'branch-admins'));

        $admin = User::where('email', 'admin.bjm@example.test')->firstOrFail();
        $this->assertTrue($admin->hasRole(UserRole::BranchAdmin->value));
        $this->assertSame($branch->id, $admin->branch_id);
    }

    public function test_branch_admin_is_scoped_to_its_own_branch_and_cannot_manage_branches(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'BRA', 'name' => 'Cabang A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'BRB', 'name' => 'Cabang B', 'city' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->post(route('master-data.store', 'pilgrims'), [
                'branch_id' => $branchB->id,
                'registration_number' => 'KODE-DARI-PENGGUNA-DIABAIKAN',
                'full_name' => 'Jamaah Cabang A',
                'gender' => 'male',
                'status' => 'registered',
            ])
            ->assertRedirect(route('master-data.index', 'pilgrims'));

        $this->assertDatabaseHas('pilgrims', [
            'registration_number' => 'BRA-JMH-00001',
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($admin)
            ->get(route('master-data.index', 'branches'))
            ->assertForbidden();
    }

    public function test_checkpoint_form_uses_map_location_picker_instead_of_manual_coordinate_fields(): void
    {
        [$admin] = $this->branchAdmin('LOC');

        $this->actingAs($admin)
            ->get(route('master-data.create', 'checkpoints'))
            ->assertOk()
            ->assertSee('Pilih Lokasi dari Peta')
            ->assertSee('Khusus Paket Perjalanan')
            ->assertSee('Khusus Rombongan')
            ->assertSee('Hasil Pilihan Peta')
            ->assertSee('data-location-picker', false)
            ->assertSee('name="departure_id"', false)
            ->assertSee('name="group_id"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false);
    }

    public function test_checkpoint_index_explains_scope_for_mobile_monitoring_and_geofence(): void
    {
        [$admin] = $this->branchAdmin('CPI');

        $this->actingAs($admin)
            ->get(route('master-data.index', 'checkpoints'))
            ->assertOk()
            ->assertSee('Titik Tujuan &amp; Kumpul', false)
            ->assertSee('Tambah Titik Tujuan &amp; Kumpul', false)
            ->assertSee('0 data');
    }

    public function test_branch_admin_manages_package_departures_from_supporting_data(): void
    {
        [$admin] = $this->branchAdmin('PKT');

        $this->actingAs($admin)
            ->get(route('master-data.index', 'departures'))
            ->assertOk()
            ->assertSee('Paket Perjalanan')
            ->assertSee('Tambah Paket Perjalanan')
            ->assertSee('Semua status')
            ->assertSee('0 data');
    }

    public function test_master_data_empty_states_explain_operational_next_steps(): void
    {
        [$admin] = $this->branchAdmin('EMP');

        $this->actingAs($admin)
            ->get(route('master-data.index', 'hotels'))
            ->assertOk()
            ->assertSee('Hotel')
            ->assertSee('Tambah Hotel')
            ->assertSee('0 data');

        $this->actingAs($admin)
            ->get(route('master-data.index', 'groups'))
            ->assertOk()
            ->assertSee('Rombongan &amp; PIN', false)
            ->assertSee('Tambah Rombongan')
            ->assertSee('0 data');
    }

    public function test_package_departure_form_guides_branch_admin_through_operational_input(): void
    {
        [$admin] = $this->branchAdmin('PFR');

        $this->actingAs($admin)
            ->get(route('master-data.create', 'departures'))
            ->assertOk()
            ->assertSee('Nama Paket')
            ->assertSee('Hotel Makkah/Madinah')
            ->assertSee('Jadwal Perjalanan per Hari')
            ->assertSee('Tampil di Landing Page')
            ->assertSee('Status')
            ->assertSee('Format: hari|judul|kota|keterangan');
    }

    public function test_hotel_form_guides_location_and_package_usage(): void
    {
        [$admin] = $this->branchAdmin('HTF');

        $this->actingAs($admin)
            ->get(route('master-data.create', 'hotels'))
            ->assertOk()
            ->assertSee('Pilih Lokasi dari Peta')
            ->assertSee('Nama Hotel')
            ->assertSee('Radius Geofence')
            ->assertSee('Hasil Pilihan Peta')
            ->assertSee('data-location-picker', false);
    }

    public function test_branch_admin_creates_tour_leader_with_a_mobile_login_account(): void
    {
        [$admin, $branch] = $this->branchAdmin('BJM');

        $this->actingAs($admin)
            ->post(route('master-data.store', 'tour-leaders'), [
                'full_name' => 'Ahmad Tour Leader',
                'phone' => '081234567890',
                'email' => 'ahmad.tl@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('master-data.index', 'tour-leaders'))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'ahmad.tl@example.test')->firstOrFail();
        $leader = TourLeader::where('employee_number', 'BJM-TL-001')->firstOrFail();

        $this->assertTrue($user->hasRole(MobileRole::TourLeader->value));
        $this->assertSame($branch->id, $user->branch_id);
        $this->assertSame($user->id, $leader->user_id);
        $this->assertSame($leader->full_name, $user->name);

        $this->postJson('/api/mobile/login', [
            'email' => 'ahmad.tl@example.test',
            'password' => 'password123',
            'device_name' => 'Feature Test',
        ])->assertOk()
            ->assertJsonPath('role', MobileRole::TourLeader->value)
            ->assertJsonPath('user.role', MobileRole::TourLeader->value);
    }

    public function test_branch_admin_creates_and_updates_muthawwif_login_account(): void
    {
        [$admin, $branch] = $this->branchAdmin('MKS');

        $this->actingAs($admin)
            ->post(route('master-data.store', 'muthawwifs'), [
                'full_name' => 'Ustaz Ibrahim',
                'phone' => '081298765432',
                'email' => 'ibrahim@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'languages' => 'Indonesia, Arab',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $muthawwif = Muthawwif::where('employee_number', 'MKS-MTF-001')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('master-data.update', ['resource' => 'muthawwifs', 'record' => $muthawwif->id]), [
                'employee_number' => 'KODE-BARU-TIDAK-BOLEH-MENGUBAH',
                'full_name' => 'Ustaz Ibrahim Updated',
                'phone' => '081298765433',
                'email' => 'ibrahim.updated@example.test',
                'languages' => 'Indonesia, Arab',
                'is_active' => '0',
            ])
            ->assertRedirect(route('master-data.index', 'muthawwifs'))
            ->assertSessionHasNoErrors();

        $muthawwif->refresh();
        $user = $muthawwif->user;

        $this->assertSame($branch->id, $user->branch_id);
        $this->assertSame('Ustaz Ibrahim Updated', $user->name);
        $this->assertSame('ibrahim.updated@example.test', $user->email);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->hasRole(MobileRole::Muthawwif->value));
        $this->assertSame('MKS-MTF-001', $muthawwif->employee_number);
    }

    public function test_operational_codes_are_generated_sequentially(): void
    {
        [$admin, $branch] = $this->branchAdmin('BJM');

        foreach (['Jamaah Satu', 'Jamaah Dua'] as $name) {
            $this->actingAs($admin)
                ->post(route('master-data.store', 'pilgrims'), [
                    'full_name' => $name,
                    'gender' => 'male',
                    'status' => 'registered',
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($admin)
            ->post(route('master-data.store', 'groups'), [
                'name' => 'Rombongan Satu',
                'capacity' => 45,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['BJM-JMH-00001', 'BJM-JMH-00002'],
            Pilgrim::orderBy('id')->pluck('registration_number')->all(),
        );
        $this->assertSame('BJM-GRP-001', Group::firstOrFail()->code);
    }

    public function test_group_rejects_a_departure_from_another_branch(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'GRA', 'name' => 'Cabang A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'GRB', 'name' => 'Cabang B', 'city' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);
        $foreignDeparture = Departure::create([
            'branch_id' => $branchB->id,
            'code' => 'DEP-B',
            'program_name' => 'Program B',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->post(route('master-data.store', 'groups'), [
                'branch_id' => $branchB->id,
                'departure_id' => $foreignDeparture->id,
                'code' => 'GROUP-X',
                'name' => 'Group Silang',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('departure_id');

        $this->assertDatabaseMissing('groups', ['code' => 'GROUP-X']);
    }

    private function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);

        return $user;
    }

    /**
     * @return array{User, Branch}
     */
    private function branchAdmin(string $code): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create([
            'code' => $code,
            'name' => "Cabang {$code}",
            'city' => 'Makassar',
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        return [$admin, $branch];
    }
}
