<?php

namespace Database\Seeders;

use App\Enums\MobileRole;
use App\Models\Branch;
use App\Models\Checkpoint;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\Muthawwif;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalPackageExampleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        DB::transaction(function (): void {
            $branch = Branch::query()->updateOrCreate(
                ['code' => 'BJM'],
                [
                    'name' => 'Cabang Banjarmasin',
                    'phone' => '6285947566363',
                    'email' => 'banjarmasin@mantauumroh.test',
                    'address' => 'Jl. A. Yani Km. 5, Banjarmasin',
                    'city' => 'Banjarmasin',
                    'province' => 'Kalimantan Selatan',
                    'is_active' => true,
                ],
            );

            $tourLeaderUser = $this->mobileUser(
                $branch,
                'tourleader.banjarmasin@mantauumroh.test',
                'Ahmad Rahman',
                '6281250001001',
                MobileRole::TourLeader,
            );
            $tourLeader = TourLeader::query()->updateOrCreate(
                ['employee_number' => 'BJM-TL-0001'],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $tourLeaderUser->id,
                    'full_name' => 'Ahmad Rahman',
                    'phone' => '6281250001001',
                    'is_active' => true,
                ],
            );

            $muthawwifUser = $this->mobileUser(
                $branch,
                'muthawwif.banjarmasin@mantauumroh.test',
                'Ustaz Abdullah Hamdan',
                '6281250001002',
                MobileRole::Muthawwif,
            );
            $muthawwif = Muthawwif::query()->updateOrCreate(
                ['employee_number' => 'BJM-MTF-0001'],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $muthawwifUser->id,
                    'full_name' => 'Ustaz Abdullah Hamdan',
                    'phone' => '6281250001002',
                    'languages' => 'Indonesia, Arab',
                    'is_active' => true,
                ],
            );

            $makkahHotel = Hotel::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'name' => 'Al Safwah Royale Orchid Makkah'],
                [
                    'city' => 'makkah',
                    'address' => 'Ajyad, area sekitar Masjidil Haram, Makkah',
                    'latitude' => 21.4208000,
                    'longitude' => 39.8248000,
                    'geofence_radius_meters' => 250,
                ],
            );
            $madinahHotel = Hotel::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'name' => 'Dallah Taibah Hotel Madinah'],
                [
                    'city' => 'madinah',
                    'address' => 'Markaziyah Utara, area sekitar Masjid Nabawi, Madinah',
                    'latitude' => 24.4707000,
                    'longitude' => 39.6119000,
                    'geofence_radius_meters' => 250,
                ],
            );

            $departureDate = today()->addMonths(2)->startOfMonth()->addDays(14);
            $departure = Departure::query()->updateOrCreate(
                ['code' => 'BJM-REG-12H-001'],
                [
                    'branch_id' => $branch->id,
                    'program_name' => 'Umroh Reguler 12 Hari',
                    'description' => 'Paket umroh reguler dengan hotel Makkah dan Madinah, pendamping perjalanan, jadwal harian, dan titik kumpul yang terhubung ke monitoring.',
                    'facilities' => implode("\n", [
                        'Tiket pesawat pergi-pulang',
                        'Visa umroh',
                        'Hotel Makkah dan Madinah',
                        'Transportasi bus AC',
                        'Makan 3 kali sehari',
                        'Manasik umroh',
                        'Tour Leader dan Muthawwif',
                        'Air zamzam sesuai ketentuan',
                        'Perlengkapan jamaah',
                    ]),
                    'requirements' => implode("\n", [
                        'KTP',
                        'Kartu Keluarga',
                        'Paspor aktif',
                        'Foto terbaru',
                        'Membayar uang muka di kantor cabang',
                    ]),
                    'departure_date' => $departureDate,
                    'return_date' => $departureDate->copy()->addDays(11),
                    'departure_airport' => 'Banjarmasin',
                    'arrival_airport' => 'Jeddah',
                    'airline' => 'Garuda Indonesia',
                    'flight_number' => 'GA-980',
                    'price' => 32_500_000,
                    'quota' => 45,
                    'is_public' => true,
                    'status' => 'scheduled',
                ],
            );

            $departure->hotels()->sync([
                $makkahHotel->id => ['sequence' => 1],
                $madinahHotel->id => ['sequence' => 2],
            ]);

            $departure->itineraries()->delete();
            foreach ($this->itinerary() as [$day, $title, $city, $description]) {
                $departure->itineraries()->create([
                    'day_number' => $day,
                    'title' => $title,
                    'city' => $city,
                    'description' => $description,
                ]);
            }

            $group = Group::query()->updateOrCreate(
                ['code' => 'BJM-GRP-REG-001'],
                [
                    'branch_id' => $branch->id,
                    'departure_id' => $departure->id,
                    'tour_leader_id' => $tourLeader->id,
                    'muthawwif_id' => $muthawwif->id,
                    'name' => 'Rombongan Reguler Banjarmasin 01',
                    'capacity' => 45,
                    'notes' => 'Rombongan untuk paket Umroh Reguler 12 Hari.',
                    'is_active' => true,
                ],
            );

            foreach ($this->checkpoints($branch, $departure, $group, $makkahHotel, $madinahHotel) as $checkpoint) {
                Checkpoint::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'name' => $checkpoint['name']],
                    $checkpoint,
                );
            }
        });

        $this->command?->info('Satu paket perjalanan operasional berhasil dibuat. Semua data berupa record normal dan bisa dihapus dari menu sistem.');
    }

    private function mobileUser(Branch $branch, string $email, string $name, string $phone, MobileRole $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => $branch->id,
                'name' => $name,
                'phone_number' => $phone,
                'password' => 'password123',
                'is_active' => true,
            ],
        );
        $user->syncRoles($role->value);

        return $user;
    }

    private function itinerary(): array
    {
        return [
            [1, 'Keberangkatan dari Indonesia', 'Banjarmasin/Jeddah', 'Jamaah berkumpul di bandara, proses check-in, dan penerbangan menuju Jeddah.'],
            [2, 'Tiba di Jeddah dan menuju Makkah', 'Jeddah/Makkah', 'Proses imigrasi, perjalanan menuju hotel Makkah, check-in, dan istirahat.'],
            [3, 'Pelaksanaan umroh pertama', 'Makkah', 'Thawaf, sai, tahallul, dan bimbingan ibadah bersama muthawwif.'],
            [4, 'Ibadah di Masjidil Haram', 'Makkah', 'Shalat berjamaah, kajian singkat, dan agenda ibadah mandiri.'],
            [5, 'Ziarah Kota Makkah', 'Makkah', 'Mengunjungi Jabal Tsur, Jabal Rahmah, Arafah, Mina, dan Muzdalifah.'],
            [6, 'Ibadah mandiri dan persiapan ke Madinah', 'Makkah', 'Ibadah mandiri, pengecekan barang, dan pengarahan perjalanan.'],
            [7, 'Perjalanan menuju Madinah', 'Makkah/Madinah', 'Check-out hotel Makkah dan perjalanan menuju Madinah.'],
            [8, 'Ibadah di Masjid Nabawi', 'Madinah', 'Shalat berjamaah dan ziarah area sekitar Masjid Nabawi.'],
            [9, 'Ziarah Kota Madinah', 'Madinah', 'Mengunjungi Masjid Quba, Jabal Uhud, dan lokasi ziarah lainnya.'],
            [10, 'Ibadah mandiri di Madinah', 'Madinah', 'Ibadah mandiri dan pendampingan jamaah.'],
            [11, 'Persiapan kepulangan', 'Madinah/Jeddah', 'Check-out hotel, perjalanan ke bandara, dan proses kepulangan.'],
            [12, 'Tiba di Indonesia', 'Indonesia', 'Jamaah tiba di Indonesia dan perjalanan selesai.'],
        ];
    }

    private function checkpoints(
        Branch $branch,
        Departure $departure,
        Group $group,
        Hotel $makkahHotel,
        Hotel $madinahHotel,
    ): array {
        return [
            [
                'branch_id' => $branch->id,
                'departure_id' => $departure->id,
                'group_id' => $group->id,
                'name' => 'Titik Kumpul Lobby Hotel Makkah',
                'category' => 'titik_kumpul',
                'city' => 'makkah',
                'address' => $makkahHotel->address,
                'latitude' => $makkahHotel->latitude,
                'longitude' => $makkahHotel->longitude,
                'geofence_radius_meters' => 150,
                'description' => 'Titik kumpul rombongan sebelum berangkat ke Masjidil Haram atau ziarah Makkah.',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch->id,
                'departure_id' => $departure->id,
                'group_id' => null,
                'name' => 'Masjidil Haram - Area King Fahd Gate',
                'category' => 'ibadah',
                'city' => 'makkah',
                'address' => 'Area King Fahd Gate, Masjidil Haram, Makkah',
                'latitude' => 21.4229000,
                'longitude' => 39.8259000,
                'geofence_radius_meters' => 300,
                'description' => 'Titik tujuan ibadah dan patokan berkumpul di area Masjidil Haram.',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch->id,
                'departure_id' => $departure->id,
                'group_id' => $group->id,
                'name' => 'Titik Kumpul Lobby Hotel Madinah',
                'category' => 'titik_kumpul',
                'city' => 'madinah',
                'address' => $madinahHotel->address,
                'latitude' => $madinahHotel->latitude,
                'longitude' => $madinahHotel->longitude,
                'geofence_radius_meters' => 150,
                'description' => 'Titik kumpul rombongan sebelum kegiatan Masjid Nabawi dan ziarah Madinah.',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch->id,
                'departure_id' => $departure->id,
                'group_id' => null,
                'name' => 'Masjid Nabawi - Area Markaziyah Utara',
                'category' => 'ibadah',
                'city' => 'madinah',
                'address' => 'Area Markaziyah Utara, Masjid Nabawi, Madinah',
                'latitude' => 24.4694000,
                'longitude' => 39.6111000,
                'geofence_radius_meters' => 300,
                'description' => 'Titik tujuan ibadah dan patokan berkumpul di area Masjid Nabawi.',
                'is_active' => true,
            ],
        ];
    }
}
