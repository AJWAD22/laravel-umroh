<?php

namespace Database\Seeders;

use App\Enums\MobileRole;
use App\Models\Branch;
use App\Models\Checkpoint;
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

        $pilgrims = collect([
            ['email' => 'jamaah@umrah.test', 'name' => 'Jamaah Mobile 001', 'number' => 'JMH-MOBILE-001', 'gender' => 'male', 'phone' => '081200000001'],
            ['email' => 'jamaah002@umrah.test', 'name' => 'Jamaah Mobile 002', 'number' => 'JMH-MOBILE-002', 'gender' => 'female', 'phone' => '081200000002'],
            ['email' => 'jamaah003@umrah.test', 'name' => 'Jamaah Mobile 003', 'number' => 'JMH-MOBILE-003', 'gender' => 'male', 'phone' => '081200000003'],
        ])->map(function (array $data) use ($branch): Pilgrim {
            $user = $this->user($branch, $data['email'], $data['name'], MobileRole::Pilgrim);

            return Pilgrim::query()->updateOrCreate(
                ['registration_number' => $data['number']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'gender' => $data['gender'],
                    'phone' => $data['phone'],
                    'status' => 'active',
                ],
            );
        })->values();

        $leaders = collect([
            ['email' => 'tourleader@umrah.test', 'name' => 'Tour Leader Mobile 001', 'number' => 'TL-MOBILE-001', 'phone' => '082100000001'],
            ['email' => 'tourleader002@umrah.test', 'name' => 'Tour Leader Mobile 002', 'number' => 'TL-MOBILE-002', 'phone' => '082100000002'],
            ['email' => 'tourleader003@umrah.test', 'name' => 'Tour Leader Mobile 003', 'number' => 'TL-MOBILE-003', 'phone' => '082100000003'],
        ])->map(function (array $data) use ($branch): TourLeader {
            $user = $this->user($branch, $data['email'], $data['name'], MobileRole::TourLeader);

            return TourLeader::query()->updateOrCreate(
                ['employee_number' => $data['number']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                ],
            );
        })->values();

        $muthawwifs = collect([
            ['email' => 'muthawwif@umrah.test', 'name' => 'Muthawwif Mobile 001', 'number' => 'MTF-MOBILE-001', 'phone' => '083100000001'],
            ['email' => 'muthawwif002@umrah.test', 'name' => 'Muthawwif Mobile 002', 'number' => 'MTF-MOBILE-002', 'phone' => '083100000002'],
            ['email' => 'muthawwif003@umrah.test', 'name' => 'Muthawwif Mobile 003', 'number' => 'MTF-MOBILE-003', 'phone' => '083100000003'],
        ])->map(function (array $data) use ($branch): Muthawwif {
            $user = $this->user($branch, $data['email'], $data['name'], MobileRole::Muthawwif);

            return Muthawwif::query()->updateOrCreate(
                ['employee_number' => $data['number']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'phone' => $data['phone'],
                    'languages' => 'Indonesia, Arab',
                    'is_active' => true,
                ],
            );
        })->values();

        $departures = collect([
            ['code' => 'DEP-MOBILE-001', 'program' => 'Umrah Reguler Demo', 'month' => 1, 'airport' => 'BDJ', 'arrival' => 'JED', 'quota' => 45],
            ['code' => 'DEP-MOBILE-002', 'program' => 'Umrah Plus Thaif Demo', 'month' => 2, 'airport' => 'BDJ', 'arrival' => 'MED', 'quota' => 40],
            ['code' => 'DEP-MOBILE-003', 'program' => 'Umrah Ramadhan Demo', 'month' => 3, 'airport' => 'BDJ', 'arrival' => 'JED', 'quota' => 50],
        ])->map(fn (array $data): Departure => Departure::query()->updateOrCreate(
            ['code' => $data['code']],
            [
                'branch_id' => $branch->id,
                'program_name' => $data['program'],
                'departure_date' => today()->addMonths($data['month']),
                'return_date' => today()->addMonths($data['month'])->addDays(10),
                'departure_airport' => $data['airport'],
                'arrival_airport' => $data['arrival'],
                'quota' => $data['quota'],
                'status' => 'scheduled',
            ],
        ))->values();

        $hotels = collect([
            ['name' => 'Hotel Demo Makkah', 'city' => 'makkah', 'address' => 'Area Ajyad, Makkah', 'lat' => 21.4205000, 'lng' => 39.8245000],
            ['name' => 'Hotel Demo Madinah', 'city' => 'madinah', 'address' => 'Area Markaziyah, Madinah', 'lat' => 24.4709000, 'lng' => 39.6122000],
            ['name' => 'Hotel Transit Jeddah', 'city' => 'other', 'address' => 'Dekat Bandara Jeddah', 'lat' => 21.6700000, 'lng' => 39.1520000],
        ])->map(fn (array $data): Hotel => Hotel::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'name' => $data['name']],
            [
                'city' => $data['city'],
                'address' => $data['address'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'geofence_radius_meters' => 250,
            ],
        ))->values();

        $departures->each(function (Departure $departure) use ($hotels): void {
            $departure->hotels()->syncWithoutDetaching([
                $hotels[0]->id => ['sequence' => 1],
                $hotels[1]->id => ['sequence' => 2],
                $hotels[2]->id => ['sequence' => 3],
            ]);
        });

        $groups = collect([
            ['code' => 'GRP-MOBILE-001', 'name' => 'Rombongan Demo Al-Ikhlas', 'notes' => 'Demo rombongan utama.'],
            ['code' => 'GRP-MOBILE-002', 'name' => 'Rombongan Demo Al-Amin', 'notes' => 'Demo rombongan keluarga.'],
            ['code' => 'GRP-MOBILE-003', 'name' => 'Rombongan Demo Safwah', 'notes' => 'Demo rombongan lansia.'],
        ])->map(function (array $data, int $index) use ($branch, $departures, $leaders, $muthawwifs, $pilgrims): Group {
            $group = Group::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'branch_id' => $branch->id,
                    'departure_id' => $departures[$index]->id,
                    'tour_leader_id' => $leaders[$index]->id,
                    'muthawwif_id' => $muthawwifs[$index]->id,
                    'name' => $data['name'],
                    'capacity' => 45,
                    'notes' => $data['notes'],
                    'is_active' => true,
                ],
            );

            GroupMember::query()->updateOrCreate(
                ['group_id' => $group->id, 'pilgrim_id' => $pilgrims[$index]->id],
                ['status' => 'active', 'joined_at' => now(), 'left_at' => null],
            );
            return $group;
        })->values();

        collect([
            ['name' => 'Masjidil Haram', 'category' => 'ibadah', 'city' => 'makkah', 'address' => 'Makkah', 'lat' => 21.4225000, 'lng' => 39.8262000, 'description' => 'Patokan umum ibadah di Makkah.', 'departure' => null, 'group' => null],
            ['name' => 'Masjid Nabawi', 'category' => 'ibadah', 'city' => 'madinah', 'address' => 'Madinah', 'lat' => 24.4672000, 'lng' => 39.6111000, 'description' => 'Patokan umum ibadah di Madinah.', 'departure' => $departures[1]->id, 'group' => null],
            ['name' => 'Titik Kumpul Bus Al-Ikhlas', 'category' => 'titik_kumpul', 'city' => 'makkah', 'address' => 'Area parkir bus rombongan Al-Ikhlas', 'lat' => 21.4199000, 'lng' => 39.8237000, 'description' => 'Titik kumpul khusus rombongan Al-Ikhlas setelah kegiatan.', 'departure' => $departures[0]->id, 'group' => $groups[0]->id],
        ])->each(fn (array $data): Checkpoint => Checkpoint::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'name' => $data['name']],
            [
                'departure_id' => $data['departure'],
                'group_id' => $data['group'],
                'category' => $data['category'],
                'city' => $data['city'],
                'address' => $data['address'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'description' => $data['description'],
                'is_active' => true,
            ],
        ));
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
