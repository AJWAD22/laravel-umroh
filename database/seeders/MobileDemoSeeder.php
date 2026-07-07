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
            ['email' => 'ahmad.fauzi@umrah.test', 'name' => 'Ahmad Fauzi Rahman', 'number' => 'BJM-JMH-2026-001', 'gender' => 'male', 'phone' => '081234567801'],
            ['email' => 'siti.aminah@umrah.test', 'name' => 'Siti Aminah Hasanah', 'number' => 'BJM-JMH-2026-002', 'gender' => 'female', 'phone' => '081234567802'],
            ['email' => 'muhammad.arsyad@umrah.test', 'name' => 'Muhammad Arsyad Al-Banjari', 'number' => 'BJM-JMH-2026-003', 'gender' => 'male', 'phone' => '081234567803'],
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
            ['email' => 'rahman.hakim@umrah.test', 'name' => 'H. Rahman Hakim', 'number' => 'BJM-TL-2026-001', 'phone' => '082156780101'],
            ['email' => 'nurul.hidayah@umrah.test', 'name' => 'Nurul Hidayah, S.E.', 'number' => 'BJM-TL-2026-002', 'phone' => '082156780102'],
            ['email' => 'zainal.abidin@umrah.test', 'name' => 'Zainal Abidin', 'number' => 'BJM-TL-2026-003', 'phone' => '082156780103'],
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
            ['email' => 'abdullah.hamdan@umrah.test', 'name' => 'Ust. Abdullah Hamdan', 'number' => 'BJM-MTF-2026-001', 'phone' => '083156780201'],
            ['email' => 'yusuf.banjari@umrah.test', 'name' => 'Ust. Yusuf Al-Banjari', 'number' => 'BJM-MTF-2026-002', 'phone' => '083156780202'],
            ['email' => 'maryam.salimah@umrah.test', 'name' => 'Ustzh. Maryam Salimah', 'number' => 'BJM-MTF-2026-003', 'phone' => '083156780203'],
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
            ['code' => 'BJM-DEP-2026-001', 'program' => 'Umrah Reguler 12 Hari - Banjarmasin', 'month' => 1, 'airport' => 'BDJ', 'arrival' => 'JED', 'quota' => 45],
            ['code' => 'BJM-DEP-2026-002', 'program' => 'Umrah Plus Thaif 13 Hari', 'month' => 2, 'airport' => 'BDJ', 'arrival' => 'MED', 'quota' => 40],
            ['code' => 'BJM-DEP-2026-003', 'program' => 'Umrah Ramadhan Awal 14 Hari', 'month' => 3, 'airport' => 'BDJ', 'arrival' => 'JED', 'quota' => 50],
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
            ['name' => 'Al Safwah Tower Makkah', 'city' => 'makkah', 'address' => 'Ajyad, sekitar Masjidil Haram, Makkah', 'lat' => 21.4206000, 'lng' => 39.8249000],
            ['name' => 'Dallah Taibah Madinah', 'city' => 'madinah', 'address' => 'Markaziyah Utara, sekitar Masjid Nabawi, Madinah', 'lat' => 24.4707000, 'lng' => 39.6119000],
            ['name' => 'Hotel Transit Al Hamra Jeddah', 'city' => 'other', 'address' => 'Area Al Hamra, Jeddah', 'lat' => 21.5269000, 'lng' => 39.1728000],
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
            ['code' => 'BJM-GRP-2026-001', 'name' => 'Rombongan Al-Ikhlas Banjarmasin', 'notes' => 'Rombongan reguler keluarga dan jamaah umum.'],
            ['code' => 'BJM-GRP-2026-002', 'name' => 'Rombongan Al-Amin Martapura', 'notes' => 'Rombongan plus Thaif dengan pendamping intensif.'],
            ['code' => 'BJM-GRP-2026-003', 'name' => 'Rombongan Safwah Ramadhan', 'notes' => 'Rombongan Ramadhan dengan prioritas jamaah lansia.'],
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
            ['name' => 'Pintu 79 King Fahd - Masjidil Haram', 'category' => 'ibadah', 'city' => 'makkah', 'address' => 'Area Pintu King Fahd, Masjidil Haram, Makkah', 'lat' => 21.4229000, 'lng' => 39.8259000, 'description' => 'Patokan bertemu jamaah setelah thawaf atau shalat berjamaah.', 'departure' => null, 'group' => null],
            ['name' => 'Pelataran Masjid Nabawi Sisi Utara', 'category' => 'ibadah', 'city' => 'madinah', 'address' => 'Area Markaziyah Utara, Masjid Nabawi, Madinah', 'lat' => 24.4682000, 'lng' => 39.6108000, 'description' => 'Titik berkumpul setelah ziarah dan shalat di Masjid Nabawi.', 'departure' => $departures[1]->id, 'group' => null],
            ['name' => 'Titik Kumpul Bus Syib Amir - Al-Ikhlas', 'category' => 'titik_kumpul', 'city' => 'makkah', 'address' => 'Area penjemputan bus Syib Amir, Makkah', 'lat' => 21.4254000, 'lng' => 39.8307000, 'description' => 'Titik kumpul khusus rombongan Al-Ikhlas sebelum kembali ke hotel.', 'departure' => $departures[0]->id, 'group' => $groups[0]->id],
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
