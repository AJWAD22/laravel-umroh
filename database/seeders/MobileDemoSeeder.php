<?php

namespace Database\Seeders;

use App\Enums\MobileRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Hotel;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Seeder;

class MobileDemoSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'BJM')->firstOrFail();

        $pilgrimUser = $this->user($branch, 'jamaah@umrah.test', 'Jamaah Mobile', MobileRole::Pilgrim);
        $leaderUser = $this->user($branch, 'tourleader@umrah.test', 'Tour Leader Mobile', MobileRole::TourLeader);
        $muthawwifUser = $this->user($branch, 'muthawwif@umrah.test', 'Muthawwif Mobile', MobileRole::Muthawwif);

        $pilgrim = Pilgrim::query()->updateOrCreate(
            ['registration_number' => 'JMH-MOBILE-001'],
            [
                'branch_id' => $branch->id,
                'user_id' => $pilgrimUser->id,
                'full_name' => 'Jamaah Mobile',
                'gender' => 'male',
                'phone' => '081200000001',
                'status' => 'active',
            ],
        );
        $leader = TourLeader::query()->updateOrCreate(
            ['employee_number' => 'TL-MOBILE-001'],
            ['branch_id' => $branch->id, 'user_id' => $leaderUser->id, 'full_name' => 'Tour Leader Mobile', 'is_active' => true],
        );
        $muthawwif = Muthawwif::query()->updateOrCreate(
            ['employee_number' => 'MTF-MOBILE-001'],
            ['branch_id' => $branch->id, 'user_id' => $muthawwifUser->id, 'full_name' => 'Muthawwif Mobile', 'is_active' => true],
        );
        $departure = Departure::query()->updateOrCreate(
            ['code' => 'DEP-MOBILE-001'],
            [
                'branch_id' => $branch->id,
                'program_name' => 'Umrah Mobile Demo',
                'departure_date' => today()->addMonth(),
                'return_date' => today()->addMonth()->addDays(10),
                'status' => 'scheduled',
            ],
        );
        $hotel = Hotel::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Hotel Demo Makkah'],
            ['city' => 'makkah', 'address' => 'Makkah', 'latitude' => 21.4205, 'longitude' => 39.8245],
        );
        $departure->hotels()->syncWithoutDetaching([$hotel->id => ['sequence' => 1]]);
        $group = Group::query()->updateOrCreate(
            ['code' => 'GRP-MOBILE-001'],
            [
                'branch_id' => $branch->id,
                'departure_id' => $departure->id,
                'tour_leader_id' => $leader->id,
                'muthawwif_id' => $muthawwif->id,
                'name' => 'Group Mobile Demo',
                'is_active' => true,
            ],
        );
        GroupMember::query()->updateOrCreate(
            ['group_id' => $group->id, 'pilgrim_id' => $pilgrim->id],
            ['status' => 'active', 'joined_at' => now(), 'left_at' => null],
        );
    }

    private function user(Branch $branch, string $email, string $name, MobileRole $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => $branch->id,
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
        $user->syncRoles($role->value);

        return $user;
    }
}
