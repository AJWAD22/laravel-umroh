<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\MobileRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MobileDevice;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\PilgrimRegistration;
use App\Models\TourLeader;
use App\Models\User;
use App\Services\MobileActivationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_can_add_and_remove_pilgrims_from_its_group(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['pilgrim_ids' => [$pilgrim->id]])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $membership = GroupMember::whereBelongsTo($group)->whereBelongsTo($pilgrim)->firstOrFail();
        $this->assertSame('active', $membership->status);

        $this->actingAs($admin)
            ->delete(route('groups.members.destroy', [$group, $membership]))
            ->assertSessionHas('success');

        $membership->refresh();
        $this->assertSame('removed', $membership->status);
        $this->assertNotNull($membership->left_at);
    }

    public function test_pilgrim_cannot_join_two_active_groups_in_the_same_departure(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        $otherGroup = Group::create([
            'branch_id' => $group->branch_id,
            'departure_id' => $group->departure_id,
            'code' => 'GRP-OTHER',
            'name' => 'Group Lain',
        ]);
        GroupMember::create([
            'group_id' => $otherGroup->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['pilgrim_ids' => [$pilgrim->id]])
            ->assertSessionHasErrors('pilgrim_ids');

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
        ]);
    }

    public function test_branch_admin_cannot_open_another_branch_group(): void
    {
        [$admin] = $this->scenario();
        $foreignBranch = Branch::create(['code' => 'FOREIGN', 'name' => 'Cabang Lain', 'city' => 'Jakarta']);
        $departure = $this->departure($foreignBranch, 'DEP-FOREIGN');
        $foreignGroup = Group::create([
            'branch_id' => $foreignBranch->id,
            'departure_id' => $departure->id,
            'code' => 'GRP-FOREIGN',
            'name' => 'Group Rahasia',
        ]);

        $this->actingAs($admin)
            ->get(route('groups.members.index', $foreignGroup))
            ->assertForbidden();
    }

    public function test_group_capacity_cannot_be_exceeded(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        $group->update(['capacity' => 1]);
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $secondPilgrim = Pilgrim::create([
            'branch_id' => $group->branch_id,
            'registration_number' => 'JMH-BJM-002',
            'full_name' => 'Jamaah Kedua',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['pilgrim_ids' => [$secondPilgrim->id]])
            ->assertSessionHasErrors('pilgrim_ids');

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'pilgrim_id' => $secondPilgrim->id,
        ]);
    }

    public function test_branch_admin_can_assign_active_staff_from_the_same_branch(): void
    {
        [$admin, $group] = $this->scenario();
        $leader = TourLeader::create([
            'branch_id' => $group->branch_id,
            'employee_number' => 'BJM-TL-001',
            'full_name' => 'Tour Leader Banjarmasin',
            'is_active' => true,
        ]);
        $muthawwif = Muthawwif::create([
            'branch_id' => $group->branch_id,
            'employee_number' => 'BJM-MTF-001',
            'full_name' => 'Muthawwif Banjarmasin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('groups.staff.update', $group), [
                'tour_leader_id' => $leader->id,
                'muthawwif_id' => $muthawwif->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame($leader->id, $group->fresh()->tour_leader_id);
        $this->assertSame($muthawwif->id, $group->fresh()->muthawwif_id);
    }

    public function test_branch_admin_can_reset_activation_pins_for_its_group(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('groups.reset-pins', $group), ['reason' => 'Jamaah mengganti perangkat sebelum berangkat'])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('reset_pins', fn (array $pins) => count($pins) === 1);

        $this->assertNotNull($pilgrim->fresh()->activation_pin_hash);
        $this->assertNotNull($pilgrim->fresh()->activation_pin_ciphertext);
        $this->assertSame($admin->id, $pilgrim->fresh()->activation_pin_created_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'activation.group_pins.reset',
            'subject_type' => Group::class,
            'subject_id' => $group->id,
        ]);

        $pin = $pilgrim->fresh()->activation_pin_ciphertext;
        $this->actingAs($admin)
            ->get(route('master-data.index', 'pilgrims'))
            ->assertOk()
            ->assertDontSee($pin);
        $this->actingAs($admin)
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee($pin);
    }

    public function test_branch_admin_can_generate_missing_pins_for_a_group_and_see_the_values(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $pilgrim->forceFill([
            'activation_pin_hash' => hash('sha256', '123456'),
            'activation_pin_ciphertext' => null,
            'activation_pin_generated_at' => now(),
        ])->save();

        $this->actingAs($admin)
            ->post(route('groups.generate-missing-pins', $group), [
                'reason' => 'Menerbitkan PIN untuk seluruh rombongan',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('reset_pins', fn (array $pins) => count($pins) === 1);

        $pilgrim->refresh();
        $this->assertNotNull($pilgrim->activation_pin_ciphertext);
        $this->actingAs($admin)
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee($pilgrim->activation_pin_ciphertext);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'activation.group_missing_pins.generated',
        ]);
    }

    public function test_group_member_page_shows_activation_operational_summary(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $pilgrim->forceFill([
            'activation_pin_hash' => hash('sha256', '123456'),
            'activation_pin_ciphertext' => '123456',
            'activation_pin_generated_at' => now(),
        ])->save();
        MobileDevice::create([
            'user_id' => $pilgrim->user_id,
            'device_uuid' => 'summary-device-001',
            'device_name' => 'HP Jamaah',
            'platform' => 'android',
            'activated_at' => now(),
            'last_used_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee('Pembayaran Lunas')
            ->assertSee('PIN Dibuat')
            ->assertSee('Aplikasi Aktif')
            ->assertSee('Alur sampai tracking muncul')
            ->assertSee('PIN dibuat dan dibagikan dari rombongan ini')
            ->assertSee('123456');
    }

    public function test_reset_pin_revokes_active_device_and_token(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $device = MobileDevice::create([
            'user_id' => $pilgrim->user_id,
            'device_uuid' => 'active-device-001',
            'device_name' => 'HP Jamaah',
            'platform' => 'android',
            'activated_at' => now(),
            'last_used_at' => now(),
        ]);
        $token = $pilgrim->user->createToken('activation-active-device-001', [MobileRole::Pilgrim->ability()]);

        $this->actingAs($admin)
            ->post(route('groups.reset-pins', $group), [
                'reason' => 'Jamaah membutuhkan PIN cadangan',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('reset_pins', fn (array $pins) => count($pins) === 1);

        $this->assertNotNull($device->fresh()->revoked_at);
        $this->withToken($token->plainTextToken)
            ->getJson(route('api.mobile.profile'))
            ->assertForbidden();
    }

    public function test_branch_admin_can_revoke_pilgrim_devices_with_reason(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $token = $pilgrim->user->createToken('activation-lost-device-001', [MobileRole::Pilgrim->ability()]);
        MobileDevice::create([
            'user_id' => $pilgrim->user_id,
            'device_uuid' => 'lost-device-001',
            'device_name' => 'HP Hilang',
            'platform' => 'android',
            'activated_at' => now(),
            'last_used_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('groups.pilgrims.revoke-devices', [$group, $pilgrim]), [
                'reason' => 'HP jamaah hilang di perjalanan',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $pilgrim->user_id,
            'device_uuid' => 'lost-device-001',
        ]);
        $this->assertNotNull(MobileDevice::where('device_uuid', 'lost-device-001')->firstOrFail()->revoked_at);
        $this->withToken($token->plainTextToken)
            ->getJson(route('api.mobile.profile'))
            ->assertForbidden();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'activation.devices.revoked',
        ]);
    }

    public function test_unpaid_pilgrim_cannot_receive_activation_pin(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario(paymentStatus: 'down_payment');
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('groups.reset-pins', $group), [
                'reason' => 'Mencoba membuat PIN sebelum lunas',
            ])
            ->assertSessionHasErrors('activation');

        $this->assertNull($pilgrim->fresh()->activation_pin_hash);
    }

    public function test_reset_activation_pins_requires_a_reason(): void
    {
        [$admin, $group] = $this->scenario();

        $this->actingAs($admin)
            ->post(route('groups.reset-pins', $group), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_expired_pin_is_rotated_automatically_and_active_device_is_revoked(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $pilgrim->forceFill([
            'activation_pin_hash' => hash_hmac('sha256', '123456', (string) config('app.key')),
            'activation_pin_ciphertext' => '123456',
            'activation_pin_created_by' => $admin->id,
            'activation_pin_generated_at' => now()->subDays(16),
        ])->save();
        $device = MobileDevice::create([
            'user_id' => $pilgrim->user_id,
            'device_uuid' => 'auto-rotation-device',
            'device_name' => 'HP Jamaah Lama',
            'platform' => 'android',
            'activated_at' => now()->subDays(16),
            'last_used_at' => now(),
        ]);
        $token = $pilgrim->user->createToken(
            'activation-auto-rotation-device',
            [MobileRole::Pilgrim->ability()],
        );
        $oldHash = $pilgrim->activation_pin_hash;

        $result = app(MobileActivationService::class)->rotateExpiredPins(15);

        $pilgrim->refresh();
        $this->assertSame(1, $result['rotated']);
        $this->assertNotSame($oldHash, $pilgrim->activation_pin_hash);
        $this->assertNotSame('123456', $pilgrim->activation_pin_ciphertext);
        $this->assertNull($pilgrim->activation_pin_created_by);
        $this->assertNotNull($device->fresh()->revoked_at);
        $this->withToken($token->plainTextToken)
            ->getJson(route('api.mobile.profile'))
            ->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activation.pins.auto_rotated',
            'actor_id' => null,
        ]);
    }

    public function test_branch_admin_can_reset_all_pins_from_package_scope(): void
    {
        [$admin, $group, $pilgrim] = $this->scenario();
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('departures.reset-pins', $group->departure), [
                'reason' => 'Rotasi keamanan seluruh paket perjalanan',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('reset_pins', fn (array $pins) => count($pins) === 1);

        $this->assertNotNull($pilgrim->fresh()->activation_pin_hash);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'activation.departure_pins.reset',
            'subject_type' => Departure::class,
            'subject_id' => $group->departure_id,
        ]);
    }

    public function test_one_package_cannot_be_assigned_to_two_active_groups(): void
    {
        [$admin, $group] = $this->scenario();

        $this->actingAs($admin)
            ->post(route('master-data.store', 'groups'), [
                'branch_id' => $group->branch_id,
                'departure_id' => $group->departure_id,
                'name' => 'Rombongan Kedua',
                'capacity' => 20,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('departure_id');
    }

    /**
     * @return array{User, Group, Pilgrim}
     */
    private function scenario(string $paymentStatus = 'paid'): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create(['code' => 'BJM', 'name' => 'Banjarmasin', 'city' => 'Banjarmasin']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);
        $departure = $this->departure($branch, 'DEP-BJM');
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'code' => 'GRP-BJM',
            'name' => 'Group Banjarmasin',
            'capacity' => 10,
        ]);
        $pilgrimUser = User::factory()->create(['branch_id' => $branch->id]);
        $pilgrimUser->assignRole(MobileRole::Pilgrim->value);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'user_id' => $pilgrimUser->id,
            'registration_number' => 'JMH-BJM-001',
            'full_name' => 'Jamaah Banjarmasin',
            'gender' => 'male',
            'status' => 'active',
        ]);
        PilgrimRegistration::create([
            'user_id' => $pilgrimUser->id,
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Banjarmasin',
            'gender' => 'male',
            'phone' => '628111111111',
            'status' => 'in_group',
            'payment_status' => $paymentStatus,
        ]);

        return [$admin, $group, $pilgrim];
    }

    private function departure(Branch $branch, string $code): Departure
    {
        return Departure::create([
            'branch_id' => $branch->id,
            'code' => $code,
            'program_name' => "Program {$branch->name}",
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(10),
            'status' => 'scheduled',
        ]);
    }
}
