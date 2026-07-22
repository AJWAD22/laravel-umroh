<?php

namespace Tests\Feature\Api;

use App\Enums\MobileRole;
use App\Events\AdminNotificationCreated;
use App\Models\Branch;
use App\Models\Checkpoint;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MobileActivationSession;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_mobile_role_can_login_and_read_its_profile(): void
    {
        $context = $this->scenario();

        foreach ([
            'pilgrimUser' => MobileRole::Pilgrim,
            'leaderUser' => MobileRole::TourLeader,
            'muthawwifUser' => MobileRole::Muthawwif,
        ] as $userKey => $role) {
            $this->withoutToken();
            $login = $this->postJson('/api/mobile/login', [
                'email' => $context[$userKey]->email,
                'password' => 'password',
                'device_name' => "phpunit-{$role->value}",
            ]);

            $login
                ->assertOk()
                ->assertJsonPath('token_type', 'Bearer')
                ->assertJsonPath('role', $role->value)
                ->assertJsonStructure(['access_token', 'user' => ['id', 'role', 'profile']]);

            $this->withToken($login->json('access_token'))
                ->getJson('/api/mobile/profile')
                ->assertOk()
                ->assertJsonPath('data.role', $role->value);
            $this->app['auth']->forgetGuards();
        }
    }

    public function test_pilgrim_location_and_history_are_scoped_to_itself(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);

        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'accuracy' => 5.5,
            'battery_level' => 87,
        ])->assertCreated()->assertJsonPath('latest_location.battery_level', 87);

        $this->assertDatabaseHas('pilgrim_locations', [
            'pilgrim_id' => $context['pilgrim']->id,
            'gps_status' => 'online',
        ]);
        $this->assertDatabaseHas('location_histories', [
            'pilgrim_id' => $context['pilgrim']->id,
        ]);
        $this->assertDatabaseMissing('location_histories', [
            'pilgrim_id' => $context['foreignPilgrim']->id,
        ]);

    }

    public function test_leaving_group_meeting_point_radius_sends_one_geofence_alert_until_reentry(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);

        Checkpoint::create([
            'branch_id' => $context['pilgrim']->branch_id,
            'group_id' => $context['group']->id,
            'name' => 'Titik Kumpul Uji',
            'category' => 'titik_kumpul',
            'city' => 'makkah',
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'is_active' => true,
        ]);

        // Posisi awal masih berada di titik kumpul, sehingga belum ada alert.
        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422487,
            'longitude' => 39.826206,
        ])->assertCreated();

        // Perpindahan sekitar satu kilometer memicu satu alert keluar radius.
        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.432487,
            'longitude' => 39.826206,
        ])->assertCreated();

        // Lokasi berikutnya masih di luar dan tidak boleh membuat alert ganda.
        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.433487,
            'longitude' => 39.826206,
        ])->assertCreated();

        Event::assertDispatchedTimes(AdminNotificationCreated::class, 1);
        Event::assertDispatched(
            AdminNotificationCreated::class,
            fn (AdminNotificationCreated $event) => $event->type === 'geofence_exit'
                && $event->data['geofence_name'] === 'Titik Kumpul Uji',
        );
    }

    public function test_tour_leader_and_muthawwif_only_see_pilgrims_in_assigned_groups(): void
    {
        $context = $this->scenario();

        $leaderToken = $this->login($context['leaderUser']);
        $this->withToken($leaderToken)
            ->getJson('/api/mobile/group-pilgrims')
            ->assertOk()
            ->assertJsonFragment(['full_name' => $context['pilgrim']->full_name])
            ->assertJsonMissing(['full_name' => $context['foreignPilgrim']->full_name]);

        $this->app['auth']->forgetGuards();
        $muthawwifToken = $this->login($context['muthawwifUser']);
        $this->withToken($muthawwifToken)
            ->getJson('/api/mobile/assigned-pilgrims')
            ->assertOk()
            ->assertJsonFragment(['full_name' => $context['pilgrim']->full_name])
            ->assertJsonMissing(['full_name' => $context['foreignPilgrim']->full_name]);

        $this->app['auth']->forgetGuards();
        $this->withToken($leaderToken)
            ->getJson('/api/mobile/assigned-pilgrims')
            ->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->withToken($muthawwifToken)
            ->postJson('/api/mobile/send-location', ['latitude' => 0, 'longitude' => 0])
            ->assertForbidden();
    }

    public function test_only_tour_leader_can_manage_mobile_meeting_points(): void
    {
        $context = $this->scenario();
        $leaderToken = $this->login($context['leaderUser']);

        $this->withToken($leaderToken)
            ->postJson('/api/mobile/staff-checkpoints', [
                'group_id' => $context['group']->id,
                'name' => 'Titik Kumpul API',
                'city' => 'makkah',
                'latitude' => 21.422487,
                'longitude' => 39.826206,
            ])
            ->assertCreated();

        $this->app['auth']->forgetGuards();
        $muthawwifToken = $this->login($context['muthawwifUser']);

        $this->withToken($muthawwifToken)
            ->getJson('/api/mobile/checkpoints')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Titik Kumpul API']);

        $this->withToken($muthawwifToken)
            ->postJson('/api/mobile/staff-checkpoints', [
                'group_id' => $context['group']->id,
                'name' => 'Tidak Boleh Dibuat',
                'city' => 'makkah',
                'latitude' => 21.422487,
                'longitude' => 39.826206,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('checkpoints', ['name' => 'Tidak Boleh Dibuat']);
    }

    public function test_group_checkpoint_does_not_leak_to_another_group_on_same_departure(): void
    {
        $context = $this->scenario();
        $otherLeaderUser = $this->mobileUser(
            $context['group']->branch,
            'api.tl.other@test.local',
            'Tour Leader Lain',
            MobileRole::TourLeader,
        );
        $otherLeader = TourLeader::create([
            'branch_id' => $context['group']->branch_id,
            'user_id' => $otherLeaderUser->id,
            'employee_number' => 'API-TL-OTHER',
            'full_name' => 'Tour Leader Lain',
        ]);
        Group::create([
            'branch_id' => $context['group']->branch_id,
            'departure_id' => $context['group']->departure_id,
            'tour_leader_id' => $otherLeader->id,
            'code' => 'API-GRP-OTHER',
            'name' => 'Group Lain',
        ]);
        Checkpoint::create([
            'branch_id' => $context['group']->branch_id,
            'departure_id' => $context['group']->departure_id,
            'group_id' => $context['group']->id,
            'name' => 'Titik Khusus Group API',
            'category' => 'titik_kumpul',
            'city' => 'makkah',
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'is_active' => true,
        ]);

        $token = $this->login($otherLeaderUser);
        $this->withToken($token)
            ->getJson('/api/mobile/checkpoints')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Titik Khusus Group API']);
    }

    public function test_mobile_endpoints_require_a_sanctum_token_and_validation_is_json(): void
    {
        $this->getJson('/api/mobile/profile')->assertUnauthorized();

        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);

        $this->withToken($token)
            ->postJson('/api/mobile/send-location', ['latitude' => 999, 'longitude' => 999])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['latitude', 'longitude']]);
    }

    public function test_authenticated_mobile_user_can_register_and_refresh_its_fcm_token(): void
    {
        $context = $this->scenario();
        $token = $this->login($context['leaderUser']);

        $payload = [
            'device_uuid' => 'device-tour-leader-001',
            'device_name' => 'Android Tour Leader',
            'platform' => 'android',
            'fcm_token' => 'fcm-token-pertama',
        ];

        $this->withToken($token)
            ->postJson('/api/mobile/device-token', $payload)
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/mobile/device-token', [
                ...$payload,
                'fcm_token' => 'fcm-token-terbaru',
            ])
            ->assertOk();

        $this->assertDatabaseCount('mobile_devices', 1);
        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $context['leaderUser']->id,
            'device_uuid' => 'device-tour-leader-001',
            'fcm_token' => 'fcm-token-terbaru',
            'revoked_at' => null,
        ]);
    }

    public function test_staff_profile_is_read_only_in_mobile_api(): void
    {
        $context = $this->scenario();

        foreach (['leaderUser', 'muthawwifUser'] as $userKey) {
            $token = $this->login($context[$userKey]);

            $this->withToken($token)
                ->getJson('/api/mobile/profile')
                ->assertOk()
                ->assertJsonStructure(['data' => ['profile' => ['photo_url']]]);

            $this->withToken($token)
                ->postJson('/api/mobile/profile/photo')
                ->assertNotFound();

            $this->app['auth']->forgetGuards();
        }
    }

    public function test_approved_activation_session_expires_when_time_has_passed(): void
    {
        $context = $this->scenario();
        $claimSecret = Str::random(64);

        $session = MobileActivationSession::create([
            'public_id' => (string) Str::uuid(),
            'pilgrim_id' => $context['pilgrim']->id,
            'created_by' => $context['leaderUser']->id,
            'approved_by' => $context['leaderUser']->id,
            'activation_token_hash' => $this->digest(Str::random(64)),
            'numeric_code_hash' => $this->digest('123456'),
            'claim_secret_hash' => $this->digest($claimSecret),
            'device_uuid' => 'expired-device-001',
            'device_name' => 'Expired Android',
            'platform' => 'android',
            'status' => 'approved',
            'claimed_at' => now()->subMinutes(20),
            'approved_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(10),
        ]);

        $response = $this->postJson('/api/mobile/activation/status', [
            'public_id' => $session->public_id,
            'claim_secret' => $claimSecret,
            'device_uuid' => 'expired-device-001',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'expired');
        $this->assertArrayNotHasKey('access_token', $response->json('data'));
        $this->assertDatabaseHas('mobile_activation_sessions', [
            'id' => $session->id,
            'status' => 'expired',
        ]);
    }

    private function login(User $user): string
    {
        $this->withoutToken();

        return $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit-device',
        ])->assertOk()->json('access_token');
    }

    /**
     * @return array<string, mixed>
     */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create(['code' => 'API-A', 'name' => 'Cabang API A', 'city' => 'Makassar']);
        $foreignBranch = Branch::create(['code' => 'API-B', 'name' => 'Cabang API B', 'city' => 'Jakarta']);

        $pilgrimUser = $this->mobileUser($branch, 'api.jamaah@test.local', 'API Jamaah', MobileRole::Pilgrim);
        $leaderUser = $this->mobileUser($branch, 'api.tl@test.local', 'API Tour Leader', MobileRole::TourLeader);
        $muthawwifUser = $this->mobileUser($branch, 'api.mtf@test.local', 'API Muthawwif', MobileRole::Muthawwif);

        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'user_id' => $pilgrimUser->id,
            'registration_number' => 'API-JMH-001',
            'full_name' => 'Jamaah Dalam Group',
            'gender' => 'male',
            'status' => 'active',
        ]);
        $foreignPilgrim = Pilgrim::create([
            'branch_id' => $foreignBranch->id,
            'registration_number' => 'API-JMH-FOREIGN',
            'full_name' => 'Jamaah Luar Group',
            'gender' => 'female',
            'status' => 'active',
        ]);
        $leader = TourLeader::create([
            'branch_id' => $branch->id,
            'user_id' => $leaderUser->id,
            'employee_number' => 'API-TL-001',
            'full_name' => 'Tour Leader API',
        ]);
        $muthawwif = Muthawwif::create([
            'branch_id' => $branch->id,
            'user_id' => $muthawwifUser->id,
            'employee_number' => 'API-MTF-001',
            'full_name' => 'Muthawwif API',
        ]);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'API-DEP-001',
            'program_name' => 'Keberangkatan API',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(10),
            'status' => 'scheduled',
        ]);
        $group = Group::create([
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'tour_leader_id' => $leader->id,
            'muthawwif_id' => $muthawwif->id,
            'code' => 'API-GRP-001',
            'name' => 'Group API',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'pilgrim_id' => $pilgrim->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return compact(
            'pilgrimUser',
            'leaderUser',
            'muthawwifUser',
            'pilgrim',
            'foreignPilgrim',
            'leader',
            'muthawwif',
            'group',
        );
    }

    private function mobileUser(Branch $branch, string $email, string $name, MobileRole $role): User
    {
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'email' => $email,
            'name' => $name,
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function digest(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
