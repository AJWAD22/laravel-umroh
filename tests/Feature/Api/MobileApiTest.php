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
use App\Models\PilgrimRegistration;
use App\Models\SosReport;
use App\Models\TourLeader;
use App\Models\User;
use App\Services\MobileActivationService;
use App\Services\FcmPushService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mockery;
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
            'branch_id' => $context['pilgrim']->branch_id,
            'gps_status' => 'online',
        ]);
        $this->assertDatabaseHas('location_histories', [
            'pilgrim_id' => $context['pilgrim']->id,
            'branch_id' => $context['pilgrim']->branch_id,
        ]);
        $this->assertDatabaseMissing('location_histories', [
            'pilgrim_id' => $context['foreignPilgrim']->id,
        ]);

    }

    public function test_location_timestamp_too_far_from_server_time_is_rejected(): void
    {
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);

        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'recorded_at' => now()->addMinutes(6)->toIso8601String(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('recorded_at');

        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'recorded_at' => now()->subDay()->subMinute()->toIso8601String(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('recorded_at');

        $this->assertDatabaseMissing('pilgrim_locations', [
            'pilgrim_id' => $context['pilgrim']->id,
        ]);
    }

    public function test_location_history_is_deduplicated_by_distance_or_interval(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);
        $baseTime = now();

        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'recorded_at' => $baseTime->toIso8601String(),
        ])->assertCreated()->assertJsonPath('history_saved', true);

        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422488,
            'longitude' => 39.826207,
            'recorded_at' => $baseTime->copy()->addSeconds(30)->toIso8601String(),
        ])->assertCreated()->assertJsonPath('history_saved', false);

        $this->assertDatabaseCount('location_histories', 1);
        $this->assertDatabaseHas('pilgrim_locations', [
            'pilgrim_id' => $context['pilgrim']->id,
            'latitude' => 21.422488,
        ]);

        $this->withToken($token)->postJson('/api/mobile/send-location', [
            'latitude' => 21.422488,
            'longitude' => 39.826207,
            'recorded_at' => $baseTime->copy()->addSeconds(61)->toIso8601String(),
        ])->assertCreated()->assertJsonPath('history_saved', true);

        $this->assertDatabaseCount('location_histories', 2);
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

    public function test_tour_leader_can_reveal_pin_only_for_assigned_pilgrim_and_action_is_audited(): void
    {
        $context = $this->scenario();
        $context['pilgrim']->forceFill([
            'activation_pin_hash' => $this->digest('483921'),
            'activation_pin_ciphertext' => '483921',
            'activation_pin_generated_at' => now(),
        ])->save();
        $leaderToken = $this->login($context['leaderUser']);

        $this->withToken($leaderToken)
            ->getJson("/api/mobile/group-pilgrims/{$context['pilgrim']->id}/activation-pin")
            ->assertOk()
            ->assertJsonPath('data.registration_number', 'API-JMH-001')
            ->assertJsonPath('data.pin', '483921');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $context['leaderUser']->id,
            'action' => 'activation.pin.revealed_by_tour_leader',
            'subject_type' => Pilgrim::class,
            'subject_id' => $context['pilgrim']->id,
        ]);

        $this->withToken($leaderToken)
            ->getJson("/api/mobile/group-pilgrims/{$context['foreignPilgrim']->id}/activation-pin")
            ->assertForbidden();
    }

    public function test_muthawwif_cannot_reveal_activation_pin(): void
    {
        $context = $this->scenario();
        $muthawwifToken = $this->login($context['muthawwifUser']);

        $this->withToken($muthawwifToken)
            ->getJson("/api/mobile/group-pilgrims/{$context['pilgrim']->id}/activation-pin")
            ->assertForbidden();
    }

    public function test_legacy_pin_must_be_reset_before_tour_leader_can_reveal_it(): void
    {
        $context = $this->scenario();
        $context['pilgrim']->forceFill([
            'activation_pin_hash' => $this->digest('123456'),
            'activation_pin_ciphertext' => null,
            'activation_pin_generated_at' => now(),
        ])->save();
        $leaderToken = $this->login($context['leaderUser']);

        $this->withToken($leaderToken)
            ->getJson("/api/mobile/group-pilgrims/{$context['pilgrim']->id}/activation-pin")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activation');
    }

    public function test_staff_location_is_rejected_when_assigned_journey_is_not_active(): void
    {
        $context = $this->scenario();
        $token = $this->login($context['leaderUser']);
        $context['group']->departure()->update(['status' => 'completed']);

        $this->withToken($token)
            ->postJson('/api/mobile/staff-location', [
                'latitude' => 21.422487,
                'longitude' => 39.826206,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Petugas belum ditugaskan ke rombongan dengan perjalanan aktif.');

        $this->assertDatabaseMissing('staff_locations', [
            'user_id' => $context['leaderUser']->id,
        ]);
    }

    public function test_only_tour_leader_can_manage_mobile_meeting_points(): void
    {
        $context = $this->scenario();
        $push = Mockery::mock(FcmPushService::class);
        $push->shouldReceive('sendToUsers')
            ->once()
            ->withArgs(fn (
                Collection $users,
                string $title,
                string $body,
                array $data,
            ) => $users->pluck('id')->contains($context['pilgrimUser']->id)
                && $title === 'Titik Kumpul Baru'
                && $data['type'] === 'checkpoint_created');
        $this->app->instance(FcmPushService::class, $push);
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

    public function test_muthawwif_can_view_but_not_acknowledge_or_resolve_sos(): void
    {
        $context = $this->scenario();
        $report = SosReport::create([
            'branch_id' => $context['branch']->id,
            'pilgrim_id' => $context['pilgrim']->id,
            'group_id' => $context['group']->id,
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'message' => 'Butuh bantuan petugas.',
            'status' => 'new',
            'reported_at' => now(),
        ]);

        $muthawwifToken = $this->login($context['muthawwifUser']);
        $this->withToken($muthawwifToken)
            ->getJson('/api/mobile/sos-reports')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Butuh bantuan petugas.']);

        $this->withToken($muthawwifToken)
            ->postJson("/api/mobile/sos-reports/{$report->id}/acknowledge")
            ->assertForbidden();

        $this->withToken($muthawwifToken)
            ->postJson("/api/mobile/sos-reports/{$report->id}/resolve")
            ->assertForbidden();
    }

    public function test_pilgrim_sos_without_gps_is_still_saved(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);
        $requestId = (string) Str::uuid();

        $this->withToken($token)
            ->postJson('/api/mobile/sos', [
                'request_id' => $requestId,
                'message' => 'Lokasi tidak terbaca, tetapi butuh bantuan.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.request_id', $requestId)
            ->assertJsonPath('data.location_status', 'unavailable')
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null);

        $this->assertDatabaseHas('sos_reports', [
            'pilgrim_id' => $context['pilgrim']->id,
            'request_id' => $requestId,
            'location_status' => 'unavailable',
            'status' => 'new',
        ]);
        $this->assertSame('sos', $context['pilgrim']->fresh()->monitoring_status);
    }

    public function test_repeated_pilgrim_sos_returns_existing_active_report(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);
        $requestId = (string) Str::uuid();

        $first = $this->withToken($token)
            ->postJson('/api/mobile/sos', [
                'request_id' => $requestId,
                'latitude' => 21.422487,
                'longitude' => 39.826206,
            ])
            ->assertCreated()
            ->json('data.id');

        $second = $this->withToken($token)
            ->postJson('/api/mobile/sos', [
                'request_id' => $requestId,
                'latitude' => 21.422487,
                'longitude' => 39.826206,
            ])
            ->assertOk()
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, SosReport::query()->where('pilgrim_id', $context['pilgrim']->id)->active()->count());
    }

    public function test_checkpoint_created_by_branch_admin_is_visible_to_assigned_pilgrim(): void
    {
        $context = $this->scenario();
        $admin = User::factory()->create(['branch_id' => $context['group']->branch_id]);
        $admin->assignRole('admin-cabang');

        $this->actingAs($admin)
            ->post(route('master-data.store', 'checkpoints'), [
                'branch_id' => $context['group']->branch_id,
                'departure_id' => $context['group']->departure_id,
                'group_id' => $context['group']->id,
                'name' => 'Titik Tujuan dari Web',
                'category' => 'titik_kumpul',
                'city' => 'makkah',
                'latitude' => 21.422487,
                'longitude' => 39.826206,
                'geofence_radius_meters' => 250,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('master-data.index', 'checkpoints'));

        // Lepaskan autentikasi guard web Admin Cabang sebelum mensimulasikan
        // request terpisah dari APK jamaah menggunakan token Sanctum.
        $this->app['auth']->forgetGuards();
        $token = $this->login($context['pilgrimUser']);
        $this->withToken($token)
            ->getJson('/api/mobile/checkpoints')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Titik Tujuan dari Web',
                'group_id' => $context['group']->id,
            ]);
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

    public function test_tracking_is_stopped_when_pilgrim_journey_is_completed(): void
    {
        $context = $this->scenario();
        $token = $this->login($context['pilgrimUser']);
        $context['group']->departure()->update(['status' => 'completed']);

        $this->withToken($token)
            ->postJson('/api/mobile/send-location', [
                'latitude' => 21.422487,
                'longitude' => 39.826206,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('journey');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withToken($token)
            ->getJson('/api/mobile/profile')
            ->assertOk();
        $this->assertDatabaseMissing('pilgrim_locations', [
            'pilgrim_id' => $context['pilgrim']->id,
        ]);
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

    public function test_pilgrim_activates_device_with_registration_number_and_pin(): void
    {
        $context = $this->scenario();
        $admin = User::factory()->create(['branch_id' => $context['branch']->id]);
        $admin->assignRole('admin-cabang');
        $pin = app(MobileActivationService::class)
            ->generatePin($admin, $context['pilgrim'], 'PIN awal untuk jamaah lunas');

        $claim = $this->postJson('/api/mobile/activation/claim', [
            'registration_number' => $context['pilgrim']->registration_number,
            'numeric_code' => $pin,
            'device_uuid' => 'activation-device-001',
            'device_name' => 'Android Jamaah',
            'platform' => 'android',
        ])->assertOk();

        $this->postJson('/api/mobile/activation/status', [
            'public_id' => $claim->json('data.public_id'),
            'claim_secret' => $claim->json('data.claim_secret'),
            'device_uuid' => 'activation-device-001',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.role', MobileRole::Pilgrim->value);
    }

    public function test_wrong_or_old_pin_is_rejected_after_reset(): void
    {
        $context = $this->scenario();
        $admin = User::factory()->create(['branch_id' => $context['branch']->id]);
        $admin->assignRole('admin-cabang');
        $oldPin = app(MobileActivationService::class)
            ->generatePin($admin, $context['pilgrim'], 'PIN awal untuk jamaah');
        $newPin = app(MobileActivationService::class)
            ->generatePin($admin, $context['pilgrim'], 'Reset PIN karena salah kirim');

        $this->postJson('/api/mobile/activation/claim', [
            'registration_number' => $context['pilgrim']->registration_number,
            'numeric_code' => $oldPin,
            'device_uuid' => 'activation-device-old',
            'device_name' => 'Android Lama',
            'platform' => 'android',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activation');

        $this->postJson('/api/mobile/activation/claim', [
            'registration_number' => $context['pilgrim']->registration_number,
            'numeric_code' => $newPin,
            'device_uuid' => 'activation-device-new',
            'device_name' => 'Android Baru',
            'platform' => 'android',
        ])->assertOk();
    }

    public function test_activation_pin_is_rejected_after_journey_is_completed(): void
    {
        $context = $this->scenario();
        $admin = User::factory()->create(['branch_id' => $context['branch']->id]);
        $admin->assignRole('admin-cabang');
        $pin = app(MobileActivationService::class)
            ->generatePin($admin, $context['pilgrim'], 'PIN sebelum perjalanan ditutup');
        $context['group']->departure()->update(['status' => 'completed']);

        $this->postJson('/api/mobile/activation/claim', [
            'registration_number' => $context['pilgrim']->registration_number,
            'numeric_code' => $pin,
            'device_uuid' => 'activation-device-completed',
            'device_name' => 'Android Jamaah',
            'platform' => 'android',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activation');
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
        PilgrimRegistration::create([
            'user_id' => $pilgrimUser->id,
            'branch_id' => $branch->id,
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Dalam Group',
            'gender' => 'male',
            'phone' => '628111111111',
            'status' => 'in_group',
            'payment_status' => 'paid',
        ]);

        return compact(
            'branch',
            'pilgrimUser',
            'leaderUser',
            'muthawwifUser',
            'pilgrim',
            'foreignPilgrim',
            'leader',
            'muthawwif',
            'departure',
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
