<?php

namespace Tests\Feature\Api;

use App\Enums\MobileRole;
use App\Events\AdminNotificationCreated;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Hotel;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

    public function test_pilgrim_location_sos_hotel_and_history_are_scoped_to_itself(): void
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

        $this->withToken($token)->postJson('/api/mobile/sos', [
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'message' => 'Saya membutuhkan bantuan.',
        ])->assertCreated()->assertJsonPath('data.status', 'active');

        $this->assertSame('sos', $context['pilgrim']->fresh()->monitoring_status);
        $this->assertDatabaseHas('sos_reports', ['pilgrim_id' => $context['pilgrim']->id]);

        $this->withToken($token)
            ->getJson('/api/mobile/hotel')
            ->assertOk()
            ->assertJsonPath('data.0.name', $context['hotel']->name);

        $this->withToken($token)
            ->getJson('/api/mobile/muthawwif-location')
            ->assertOk()
            ->assertJsonPath('data.full_name', $context['muthawwif']->full_name)
            ->assertJsonPath('data.location_available', false);

        $this->withToken($token)
            ->getJson('/api/mobile/my-location-history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.battery_level', 87);
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

    public function test_staff_can_only_view_profile_photo_and_cannot_replace_it(): void
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
                ->assertForbidden();

            $this->app['auth']->forgetGuards();
        }
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
        $hotel = Hotel::create([
            'branch_id' => $branch->id,
            'name' => 'Hotel API',
            'city' => 'makkah',
            'latitude' => 21.42,
            'longitude' => 39.82,
        ]);
        $departure->hotels()->attach($hotel->id, ['sequence' => 1]);
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
            'hotel',
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
}
