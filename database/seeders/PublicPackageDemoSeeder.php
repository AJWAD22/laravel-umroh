<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Departure;
use App\Models\Hotel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PublicPackageDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $branch = Branch::query()->where('is_active', true)->oldest('id')->first()
                ?? Branch::query()->create([
                    'code' => 'DEMO',
                    'name' => 'Cabang Utama',
                    'city' => 'Banjarmasin',
                    'province' => 'Kalimantan Selatan',
                    'is_active' => true,
                ]);

            $hotels = collect([
                ['name' => 'Akomodasi Makkah Bintang 4', 'city' => 'makkah', 'address' => 'Area pusat Makkah', 'latitude' => 21.422487, 'longitude' => 39.826206],
                ['name' => 'Akomodasi Madinah Bintang 4', 'city' => 'madinah', 'address' => 'Area pusat Madinah', 'latitude' => 24.467213, 'longitude' => 39.611193],
                ['name' => 'Akomodasi Makkah Bintang 5', 'city' => 'makkah', 'address' => 'Area sekitar Masjidil Haram', 'latitude' => 21.420700, 'longitude' => 39.824900],
                ['name' => 'Akomodasi Madinah Bintang 5', 'city' => 'madinah', 'address' => 'Area sekitar Masjid Nabawi', 'latitude' => 24.469400, 'longitude' => 39.611100],
            ])->map(fn (array $hotel) => Hotel::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'name' => $hotel['name']],
                [...$hotel, 'geofence_radius_meters' => 250],
            ));

            $packages = [
                [
                    'code' => 'PKT-DEMO-001',
                    'name' => 'Umroh Hemat 9 Hari',
                    'description' => 'Contoh paket ekonomis dengan agenda inti Makkah dan Madinah serta pendampingan rombongan.',
                    'offset' => 2,
                    'duration' => 9,
                    'airline' => 'Saudia',
                    'flight' => 'SV (contoh)',
                    'price' => 28_900_000,
                    'quota' => 45,
                    'hotel_indexes' => [0, 1],
                    'itinerary' => [
                        [1, 'Keberangkatan dari Indonesia', 'Jeddah', 'Penerbangan, proses imigrasi, dan perjalanan menuju hotel.'],
                        [2, 'Persiapan dan pelaksanaan umroh', 'Makkah', 'Thawaf, sai, dan tahallul bersama pembimbing.'],
                        [4, 'Ziarah Kota Makkah', 'Makkah', 'Kunjungan lokasi bersejarah dan penguatan manasik.'],
                        [6, 'Perjalanan menuju Madinah', 'Madinah', 'Check-out hotel dan perjalanan menuju Madinah.'],
                        [7, 'Ibadah dan ziarah Madinah', 'Madinah', 'Agenda Masjid Nabawi dan ziarah sekitar Madinah.'],
                        [9, 'Kepulangan ke Indonesia', 'Madinah', 'Persiapan kepulangan dan perjalanan menuju bandara.'],
                    ],
                ],
                [
                    'code' => 'PKT-DEMO-002',
                    'name' => 'Umroh Reguler 12 Hari',
                    'description' => 'Contoh paket reguler dengan waktu ibadah lebih lapang, hotel nyaman, dan agenda ziarah terstruktur.',
                    'offset' => 3,
                    'duration' => 12,
                    'airline' => 'Garuda Indonesia',
                    'flight' => 'GA (contoh)',
                    'price' => 34_900_000,
                    'quota' => 40,
                    'hotel_indexes' => [2, 3],
                    'itinerary' => [
                        [1, 'Keberangkatan dan transit', 'Jeddah', 'Penerbangan dari Indonesia dan proses kedatangan.'],
                        [2, 'Umroh pertama', 'Makkah', 'Pelaksanaan rangkaian ibadah umroh bersama pembimbing.'],
                        [4, 'Ziarah Makkah', 'Makkah', 'Agenda ziarah dan pengenalan lokasi bersejarah.'],
                        [6, 'Ibadah mandiri terarah', 'Makkah', 'Waktu ibadah dengan pendampingan petugas.'],
                        [8, 'Perjalanan ke Madinah', 'Madinah', 'Perpindahan hotel dan orientasi area Masjid Nabawi.'],
                        [9, 'Ziarah Madinah', 'Madinah', 'Agenda ziarah dan ibadah di Madinah.'],
                        [12, 'Kepulangan', 'Madinah', 'Check-out, perjalanan bandara, dan penerbangan pulang.'],
                    ],
                ],
                [
                    'code' => 'PKT-DEMO-003',
                    'name' => 'Umroh Plus Thaif 12 Hari',
                    'description' => 'Contoh paket dengan tambahan kunjungan Thaif, akomodasi pilihan, dan jadwal perjalanan yang lebih lengkap.',
                    'offset' => 4,
                    'duration' => 12,
                    'airline' => 'Qatar Airways',
                    'flight' => 'QR (contoh)',
                    'price' => 39_900_000,
                    'quota' => 35,
                    'hotel_indexes' => [2, 3],
                    'itinerary' => [
                        [1, 'Keberangkatan dari Indonesia', 'Jeddah', 'Penerbangan menuju Arab Saudi dan proses kedatangan.'],
                        [2, 'Pelaksanaan umroh', 'Makkah', 'Thawaf, sai, dan tahallul bersama pembimbing.'],
                        [4, 'Ziarah Makkah', 'Makkah', 'Kunjungan lokasi bersejarah di sekitar Makkah.'],
                        [6, 'Kunjungan Thaif', 'other', 'Perjalanan dan kunjungan terjadwal ke kawasan Thaif.'],
                        [8, 'Perjalanan ke Madinah', 'Madinah', 'Perpindahan menuju Madinah dan check-in hotel.'],
                        [9, 'Ziarah Madinah', 'Madinah', 'Ibadah dan kunjungan lokasi bersejarah di Madinah.'],
                        [12, 'Kepulangan ke Indonesia', 'Madinah', 'Persiapan kepulangan dan perjalanan menuju bandara.'],
                    ],
                ],
            ];

            foreach ($packages as $item) {
                $departureDate = today()->addMonths($item['offset'])->startOfMonth()->addDays(9);
                $departure = Departure::query()->updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'branch_id' => $branch->id,
                        'program_name' => $item['name'],
                        'description' => $item['description'],
                        'departure_date' => $departureDate,
                        'return_date' => $departureDate->copy()->addDays($item['duration'] - 1),
                        'departure_airport' => 'BDJ',
                        'arrival_airport' => 'JED',
                        'airline' => $item['airline'],
                        'flight_number' => $item['flight'],
                        'price' => $item['price'],
                        'quota' => $item['quota'],
                        'is_public' => true,
                        'status' => 'scheduled',
                    ],
                );

                $sync = [];
                foreach ($item['hotel_indexes'] as $sequence => $hotelIndex) {
                    $sync[$hotels[$hotelIndex]->id] = ['sequence' => $sequence + 1];
                }
                $departure->hotels()->sync($sync);

                $departure->itineraries()->delete();
                foreach ($item['itinerary'] as [$day, $title, $city, $description]) {
                    $departure->itineraries()->create([
                        'day_number' => $day,
                        'title' => $title,
                        'city' => $city,
                        'description' => $description,
                    ]);
                }
            }
        });

        $this->command?->info('Tiga paket contoh publik berhasil disiapkan tanpa mengubah data jamaah.');
    }
}
