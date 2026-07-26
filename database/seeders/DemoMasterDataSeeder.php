<?php

namespace Database\Seeders;

use App\Enums\MobileRole;
use App\Models\Branch;
use App\Models\Checkpoint;
use App\Models\Departure;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Hotel;
use App\Models\LocationHistory;
use App\Models\MobileActivationSession;
use App\Models\MobileDevice;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\SosReport;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class DemoMasterDataSeeder extends Seeder
{
    /**
     * @var array<string, int>
     */
    private array $deleted = [
        'location_histories' => 0,
        'pilgrim_locations' => 0,
        'sos_reports' => 0,
        'mobile_activation_sessions' => 0,
        'mobile_devices' => 0,
        'group_members' => 0,
        'pilgrims' => 0,
        'groups' => 0,
        'tour_leaders' => 0,
        'muthawwifs' => 0,
        'users' => 0,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->deleteOldMasterData();
            $this->deleteLegacyBranchAdmins();

            $branches = $this->ensureBranches();
            $this->ensureBranchAdmins($branches);
            $this->ensureGeneralCheckpoints($branches);
            $departures = $this->ensureDepartures($branches);
            $this->ensureHotels($branches, $departures);
            $leaders = $this->seedTourLeaders($branches);
            $muthawwifs = $this->seedMuthawwifs($branches);
            $groups = $this->seedGroups($branches, $departures, $leaders, $muthawwifs);
            $this->ensureGroupCheckpoints($branches, $departures, $groups);
            $this->seedPilgrims($branches, $groups);
        });

        $this->command?->info('Demo master data Mantau Umroh berhasil disiapkan.');
        foreach ($this->deleted as $table => $count) {
            $this->command?->line("Dihapus {$table}: {$count}");
        }
        $this->command?->line('Data baru: 1 Tour Leader, 1 Muthawwif, 2 Rombongan, 30 Jamaah.');
        $this->command?->line('Tujuan umum: Masjidil Haram/Ka’bah, Masjid Nabawi, Bandara Jeddah, Miqat Tan’im, Jabal Rahmah.');
        $this->command?->line('Akun Admin Cabang demo: adminbjm@mantauumrah.id');
        $this->command?->line('Password semua Tour Leader dan Muthawwif: password123');
        $this->command?->line('Password semua Akun Admin Cabang demo: password123');
    }

    private function deleteOldMasterData(): void
    {
        $mobileRoleNames = [
            MobileRole::Pilgrim->value,
            MobileRole::TourLeader->value,
            MobileRole::Muthawwif->value,
        ];

        $mobileRoleIds = Role::query()
            ->whereIn('name', $mobileRoleNames)
            ->where('guard_name', 'web')
            ->pluck('id');

        $userIds = collect()
            ->merge(Pilgrim::withTrashed()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(TourLeader::withTrashed()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(Muthawwif::withTrashed()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('role_id', $mobileRoleIds)
                ->pluck('model_id'))
            ->filter()
            ->unique()
            ->values();

        $adminUserIds = User::query()
            ->whereIn('id', $userIds)
            ->whereHas('roles', fn ($query) => $query
                ->whereIn('name', ['super-admin', 'admin-cabang']))
            ->pluck('id');
        $deletableUserIds = $userIds->diff($adminUserIds)->values();

        $pilgrimIds = Pilgrim::withTrashed()->pluck('id');
        $groupIds = Group::withTrashed()->pluck('id');

        if ($pilgrimIds->isNotEmpty()) {
            $this->deleted['location_histories'] = LocationHistory::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->delete();
            $this->deleted['pilgrim_locations'] = PilgrimLocation::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->delete();
            $this->deleted['sos_reports'] = SosReport::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->delete();
            $this->deleted['mobile_activation_sessions'] = MobileActivationSession::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->delete();
        }

        if ($deletableUserIds->isNotEmpty()) {
            $this->deleted['mobile_devices'] = MobileDevice::query()
                ->whereIn('user_id', $deletableUserIds)
                ->delete();

            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $deletableUserIds)
                ->delete();

            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $deletableUserIds)
                ->delete();
        }

        if ($groupIds->isNotEmpty()) {
            $this->deleted['group_members'] = GroupMember::query()
                ->whereIn('group_id', $groupIds)
                ->delete();
        }

        $this->deleted['pilgrims'] = Pilgrim::withTrashed()->forceDelete();
        $this->deleted['groups'] = Group::withTrashed()->forceDelete();
        $this->deleted['tour_leaders'] = TourLeader::withTrashed()->forceDelete();
        $this->deleted['muthawwifs'] = Muthawwif::withTrashed()->forceDelete();

        if ($deletableUserIds->isNotEmpty()) {
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $deletableUserIds)
                ->delete();
            $this->deleted['users'] = User::query()
                ->whereIn('id', $deletableUserIds)
                ->delete();
        }
    }

    private function deleteLegacyBranchAdmins(): void
    {
        User::query()
            ->whereIn('email', ['admin.cabang@umrah.test', 'adminbjm@umrah.test'])
            ->get()
            ->each(function (User $user): void {
                $user->syncRoles([]);
                $user->delete();
            });
    }

    /**
     * @return Collection<string, Branch>
     */
    private function ensureBranches(): Collection
    {
        return collect([
            'Banjarmasin' => [
                'code' => 'BJM',
                'name' => 'Cabang Banjarmasin',
                'phone' => '0511-6701001',
                'email' => 'banjarmasin@mantauumroh.id',
                'address' => 'Jl. Ahmad Yani KM 4,5, Banjarmasin',
                'city' => 'Banjarmasin',
                'province' => 'Kalimantan Selatan',
            ],
        ])->mapWithKeys(function (array $data, string $city): array {
            $branch = Branch::withTrashed()
                ->where(fn ($query) => $query
                    ->where('city', $city)
                    ->orWhere('name', 'like', "%{$city}%")
                    ->orWhere('code', $data['code']))
                ->first();

            if ($branch) {
                $branch->restore();
                $branch->fill($data + ['is_active' => true])->save();

                return [$city => $branch->fresh()];
            }

            return [$city => Branch::query()->create($data + ['is_active' => true])];
        });
    }

    /**
     * @param Collection<string, Branch> $branches
     */
    private function ensureBranchAdmins(Collection $branches): void
    {
        collect([
            'Banjarmasin' => ['name' => 'Admin Cabang Banjarmasin', 'email' => 'adminbjm@mantauumrah.id', 'phone' => '08115001001'],
        ])->each(function (array $data, string $city) use ($branches): void {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'branch_id' => $branches[$city]->id,
                    'name' => $data['name'],
                    'phone_number' => $data['phone'],
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles('admin-cabang');
        });
    }

    /**
     * @param Collection<string, Branch> $branches
     */
    private function ensureGeneralCheckpoints(Collection $branches): void
    {
        if (! Schema::hasTable('checkpoints')) {
            return;
        }

        $checkpoints = [
            [
                'name' => 'Masjidil Haram / Ka’bah',
                'category' => 'ibadah',
                'city' => 'makkah',
                'address' => 'Area Masjidil Haram, Makkah',
                'latitude' => 21.4224870,
                'longitude' => 39.8262060,
                'description' => 'Tujuan utama ibadah umroh. Gunakan sebagai patokan area Masjidil Haram dan Ka’bah.',
            ],
            [
                'name' => 'Masjid Nabawi',
                'category' => 'ibadah',
                'city' => 'madinah',
                'address' => 'Area Masjid Nabawi, Madinah',
                'latitude' => 24.4672130,
                'longitude' => 39.6111930,
                'description' => 'Tujuan ibadah dan ziarah utama di Madinah.',
            ],
            [
                'name' => 'Bandara King Abdulaziz Jeddah',
                'category' => 'transportasi',
                'city' => 'jeddah',
                'address' => 'King Abdulaziz International Airport, Jeddah',
                'latitude' => 21.6702330,
                'longitude' => 39.1527790,
                'description' => 'Bandara kedatangan atau kepulangan jamaah di Jeddah.',
            ],
            [
                'name' => 'Miqat Masjid Aisyah / Tan’im',
                'category' => 'ibadah',
                'city' => 'makkah',
                'address' => 'Masjid Aisyah, Tan’im, Makkah',
                'latitude' => 21.4670780,
                'longitude' => 39.7876090,
                'description' => 'Titik miqat yang umum digunakan jamaah untuk mengambil niat umroh.',
            ],
            [
                'name' => 'Jabal Rahmah',
                'category' => 'lainnya',
                'city' => 'makkah',
                'address' => 'Arafah, Makkah',
                'latitude' => 21.3549720,
                'longitude' => 39.9840080,
                'description' => 'Lokasi ziarah di area Arafah.',
            ],
        ];

        foreach ($branches as $branch) {
            foreach ($checkpoints as $data) {
                $checkpoint = Checkpoint::withTrashed()
                    ->where('branch_id', $branch->id)
                    ->where('name', $data['name'])
                    ->first();

                if ($checkpoint) {
                    $checkpoint->restore();
                    $checkpoint->fill($data + [
                        'branch_id' => $branch->id,
                        'departure_id' => null,
                        'group_id' => null,
                        'is_active' => true,
                    ])->save();

                    continue;
                }

                Checkpoint::query()->create($data + [
                    'branch_id' => $branch->id,
                    'departure_id' => null,
                    'group_id' => null,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * @param Collection<string, Branch> $branches
     * @return Collection<string, TourLeader>
     */
    private function seedTourLeaders(Collection $branches): Collection
    {
        return collect([
            'Padil Banjarmasin' => ['email' => 'padilbjm@mantauumrah.id', 'phone' => '081298761001', 'branch' => 'Banjarmasin', 'number' => 'TL-250001'],
        ])->mapWithKeys(function (array $data, string $name) use ($branches): array {
            $branch = $branches[$data['branch']];
            $user = $this->staffUser($branch, $name, $data['email'], $data['phone'], MobileRole::TourLeader);

            return [$name => TourLeader::query()->updateOrCreate(
                ['employee_number' => $data['number']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'phone' => $data['phone'],
                    'photo_path' => null,
                    'is_active' => true,
                ],
            )];
        });
    }

    /**
     * @param Collection<string, Branch> $branches
     * @return Collection<string, Muthawwif>
     */
    private function seedMuthawwifs(Collection $branches): Collection
    {
        return collect([
            'Hafis Banjarmasin' => ['email' => 'hafisbjm@mantauumrah.id', 'phone' => '081355660001', 'branch' => 'Banjarmasin', 'number' => 'MTF-250001', 'languages' => 'Bahasa Indonesia dan Arab / Makkah, Madinah, dan Ziarah'],
        ])->mapWithKeys(function (array $data, string $name) use ($branches): array {
            $branch = $branches[$data['branch']];
            $user = $this->staffUser($branch, $name, $data['email'], $data['phone'], MobileRole::Muthawwif);

            return [$name => Muthawwif::query()->updateOrCreate(
                ['employee_number' => $data['number']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'phone' => $data['phone'],
                    'photo_path' => null,
                    'languages' => $data['languages'],
                    'is_active' => true,
                ],
            )];
        });
    }

    /**
     * @param Collection<string, Branch> $branches
     * @return Collection<string, Departure>
     */
    private function ensureDepartures(Collection $branches): Collection
    {
        return collect([
            'Al Hijrah 01' => [
                'code' => 'DEP-RH-001',
                'branch' => 'Banjarmasin',
                'program' => 'Umroh Reguler Al Hijrah Januari 2027',
                'description' => 'Paket umroh 13 hari dengan hotel dekat Masjidil Haram dan Masjid Nabawi, pendamping berpengalaman, serta monitoring jamaah selama perjalanan.',
                'departure' => '2027-01-10',
                'return' => '2027-01-22',
                'airline' => 'Garuda Indonesia',
                'flight' => 'GA-980',
                'price' => 32_500_000,
            ],
            'Al Hijrah 02' => [
                'code' => 'DEP-RH-002',
                'branch' => 'Banjarmasin',
                'program' => 'Umroh Hemat Al Hijrah Februari 2027',
                'description' => 'Paket umroh hemat 13 hari dengan fasilitas inti, hotel nyaman, manasik, dan pendamping perjalanan untuk jamaah Banjarmasin.',
                'departure' => '2027-02-10',
                'return' => '2027-02-22',
                'airline' => 'Lion Air',
                'flight' => 'JT-108',
                'price' => 28_900_000,
            ],
        ])->mapWithKeys(function (array $data, string $groupName) use ($branches): array {
            $departure = Departure::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'branch_id' => $branches[$data['branch']]->id,
                    'program_name' => $data['program'],
                    'description' => $data['description'],
                    'facilities' => "Visa umroh\nManasik sebelum keberangkatan\nHotel Makkah dan Madinah sesuai paket\nTransportasi bus AC\nPendamping Tour Leader dan Muthawwif\nAkses informasi jadwal dan rombongan melalui aplikasi",
                    'requirements' => "KTP dan Kartu Keluarga\nPaspor aktif minimal 7 bulan\nPas foto sesuai ketentuan\nBuku nikah untuk pasangan suami istri\nPembayaran melalui kantor cabang",
                    'departure_date' => $data['departure'],
                    'return_date' => $data['return'],
                    'departure_airport' => 'Banjarmasin BDJ',
                    'arrival_airport' => 'Jeddah JED',
                    'airline' => $data['airline'],
                    'flight_number' => $data['flight'],
                    'price' => $data['price'],
                    'quota' => 45,
                    'is_public' => true,
                    'status' => 'scheduled',
                ],
            );

            $this->syncItinerary($departure);

            return [$groupName => $departure];
        });
    }

    private function syncItinerary(Departure $departure): void
    {
        $departure->itineraries()->delete();

        foreach ($this->itineraryRows() as [$day, $title, $city, $description]) {
            $departure->itineraries()->create([
                'day_number' => $day,
                'title' => $title,
                'city' => $city,
                'description' => $description,
            ]);
        }
    }

    private function itineraryRows(): array
    {
        return [
            [1, 'Keberangkatan dari Banjarmasin', 'Banjarmasin', 'Jamaah berkumpul di bandara, briefing rombongan, dan penerbangan menuju Jeddah.'],
            [2, 'Tiba di Jeddah dan menuju Makkah', 'Jeddah', 'Proses imigrasi, perjalanan bus menuju Makkah, check-in hotel, dan istirahat.'],
            [3, 'Pelaksanaan umroh pertama', 'Makkah', 'Thawaf, sai, tahallul, dan pendampingan ibadah oleh Muthawwif.'],
            [4, 'Ibadah mandiri di Masjidil Haram', 'Makkah', 'Shalat berjamaah, tilawah, dan pengarahan titik kumpul hotel.'],
            [5, 'Ziarah sekitar Makkah', 'Makkah', 'Ziarah Jabal Tsur, Jabal Rahmah, Arafah, Muzdalifah, Mina, dan Miqat Tanim.'],
            [6, 'Program ibadah dan manasik lanjutan', 'Makkah', 'Kajian singkat, evaluasi rombongan, dan ibadah mandiri.'],
            [7, 'Persiapan keberangkatan ke Madinah', 'Makkah', 'Thawaf wada sesuai arahan pembimbing dan persiapan check-out hotel.'],
            [8, 'Perjalanan Makkah ke Madinah', 'Madinah', 'Perjalanan bus menuju Madinah, check-in hotel, dan orientasi area Masjid Nabawi.'],
            [9, 'Ibadah di Masjid Nabawi', 'Madinah', 'Shalat berjamaah, kunjungan Raudhah sesuai jadwal, dan pengarahan titik kumpul.'],
            [10, 'Ziarah Madinah', 'Madinah', 'Ziarah Masjid Quba, Jabal Uhud, Masjid Qiblatain, dan Kebun Kurma.'],
            [11, 'Ibadah mandiri dan belanja oleh-oleh', 'Madinah', 'Jamaah mengikuti jadwal ibadah dan waktu belanja sesuai arahan petugas.'],
            [12, 'Persiapan kepulangan', 'Madinah', 'Check-out hotel, perjalanan ke bandara, dan proses kepulangan ke Indonesia.'],
            [13, 'Tiba di Indonesia', 'Banjarmasin', 'Jamaah tiba di Indonesia dan proses penjemputan keluarga.'],
        ];
    }

    /**
     * @param Collection<string, Branch> $branches
     * @param Collection<string, Departure> $departures
     */
    private function ensureHotels(Collection $branches, Collection $departures): void
    {
        $hotels = [
            'Banjarmasin' => [
                ['name' => 'Al Safwah Tower Makkah', 'city' => 'makkah', 'address' => 'Ajyad, sekitar Masjidil Haram, Makkah', 'lat' => 21.4206000, 'lng' => 39.8249000, 'sequence' => 1],
                ['name' => 'Dallah Taibah Madinah', 'city' => 'madinah', 'address' => 'Markaziyah Utara, sekitar Masjid Nabawi, Madinah', 'lat' => 24.4707000, 'lng' => 39.6119000, 'sequence' => 2],
            ],
            'Banjarbaru' => [
                ['name' => 'Anjum Hotel Makkah', 'city' => 'makkah', 'address' => 'Jabal Omar, Makkah', 'lat' => 21.4238000, 'lng' => 39.8226000, 'sequence' => 1],
                ['name' => 'Pullman Zamzam Madinah', 'city' => 'madinah', 'address' => 'Area Masjid Nabawi, Madinah', 'lat' => 24.4669000, 'lng' => 39.6123000, 'sequence' => 2],
            ],
            'Martapura' => [
                ['name' => 'Swissotel Makkah', 'city' => 'makkah', 'address' => 'Abraj Al Bait, Makkah', 'lat' => 21.4197000, 'lng' => 39.8255000, 'sequence' => 1],
                ['name' => 'Madinah Hilton', 'city' => 'madinah', 'address' => 'King Fahd Road, Madinah', 'lat' => 24.4694000, 'lng' => 39.6111000, 'sequence' => 2],
            ],
        ];

        foreach ($departures as $groupName => $departure) {
            $branchCity = match ($groupName) {
                'Al Hijrah 01' => 'Banjarmasin',
                'Al Hijrah 02' => 'Banjarbaru',
                default => 'Martapura',
            };

            $sync = [];
            foreach ($hotels[$branchCity] as $hotelData) {
                $hotel = Hotel::query()->updateOrCreate(
                    ['branch_id' => $branches[$branchCity]->id, 'name' => $hotelData['name']],
                    [
                        'city' => $hotelData['city'],
                        'address' => $hotelData['address'],
                        'latitude' => $hotelData['lat'],
                        'longitude' => $hotelData['lng'],
                        'geofence_radius_meters' => 250,
                    ],
                );
                $sync[$hotel->id] = ['sequence' => $hotelData['sequence']];
            }

            $departure->hotels()->sync($sync);
        }
    }

    /**
     * @param Collection<string, Branch> $branches
     * @param Collection<string, Departure> $departures
     * @param Collection<string, TourLeader> $leaders
     * @param Collection<string, Muthawwif> $muthawwifs
     * @return Collection<string, Group>
     */
    private function seedGroups(Collection $branches, Collection $departures, Collection $leaders, Collection $muthawwifs): Collection
    {
        return collect([
            'Al Hijrah 01' => ['code' => 'RH-001', 'branch' => 'Banjarmasin', 'leader' => 'Muhammad Arif', 'muthawwif' => 'Ust. Abdullah', 'notes' => 'Rombongan keberangkatan Januari 2027 dari Cabang Banjarmasin'],
            'Al Hijrah 02' => ['code' => 'RH-002', 'branch' => 'Banjarbaru', 'leader' => 'Agus Salim', 'muthawwif' => 'Ust. Hasan Basri', 'notes' => 'Rombongan keberangkatan Februari 2027 dari Cabang Banjarbaru'],
            'Al Hijrah 03' => ['code' => 'RH-003', 'branch' => 'Martapura', 'leader' => 'Fajar Hidayat', 'muthawwif' => 'Ust. Syamsuddin', 'notes' => 'Rombongan keberangkatan Maret 2027 dari Cabang Martapura'],
        ])->mapWithKeys(fn (array $data, string $name): array => [$name => Group::query()->updateOrCreate(
            ['code' => $data['code']],
            [
                'branch_id' => $branches[$data['branch']]->id,
                'departure_id' => $departures[$name]->id,
                'tour_leader_id' => $leaders[$data['leader']]->id,
                'muthawwif_id' => $muthawwifs[$data['muthawwif']]->id,
                'name' => $name,
                'capacity' => 45,
                'notes' => $data['notes'],
                'is_active' => true,
            ],
        )]);
    }

    /**
     * @param Collection<string, Branch> $branches
     * @param Collection<string, Departure> $departures
     * @param Collection<string, Group> $groups
     */
    private function ensureGroupCheckpoints(Collection $branches, Collection $departures, Collection $groups): void
    {
        $rows = [
            'Al Hijrah 01' => [
                ['Lobi Al Safwah Tower', 'hotel', 'makkah', 'Titik kumpul jamaah sebelum menuju Masjidil Haram.', 21.4206000, 39.8249000, 180],
                ['Pintu 79 Masjidil Haram', 'titik_kumpul', 'makkah', 'Titik kumpul setelah thawaf dan sai.', 21.4219000, 39.8257000, 120],
                ['Lobi Dallah Taibah Madinah', 'hotel', 'madinah', 'Titik kumpul keberangkatan ziarah Madinah.', 24.4707000, 39.6119000, 180],
            ],
            'Al Hijrah 02' => [
                ['Lobi Anjum Hotel Makkah', 'hotel', 'makkah', 'Titik kumpul rombongan sebelum ibadah bersama.', 21.4238000, 39.8226000, 180],
                ['Area Bus Jabal Omar', 'titik_kumpul', 'makkah', 'Titik kumpul naik bus ziarah Makkah.', 21.4243000, 39.8219000, 150],
                ['Lobi Pullman Zamzam Madinah', 'hotel', 'madinah', 'Titik kumpul jamaah saat agenda Masjid Nabawi.', 24.4669000, 39.6123000, 180],
            ],
            'Al Hijrah 03' => [
                ['Lobi Swissotel Makkah', 'hotel', 'makkah', 'Titik kumpul jamaah premium sebelum kegiatan harian.', 21.4197000, 39.8255000, 180],
                ['Pelataran Abraj Al Bait', 'titik_kumpul', 'makkah', 'Titik kumpul setelah ibadah mandiri di Masjidil Haram.', 21.4199000, 39.8260000, 130],
                ['Lobi Madinah Hilton', 'hotel', 'madinah', 'Titik kumpul sebelum ziarah Madinah.', 24.4694000, 39.6111000, 180],
            ],
        ];

        foreach ($rows as $groupName => $checkpoints) {
            $group = $groups[$groupName];
            $departure = $departures[$groupName];
            $branch = $branches[$group->branch->city] ?? $group->branch;

            foreach ($checkpoints as [$name, $category, $city, $description, $latitude, $longitude, $radius]) {
                $checkpoint = Checkpoint::withTrashed()
                    ->where('branch_id', $branch->id)
                    ->where('group_id', $group->id)
                    ->where('name', $name)
                    ->first();

                $payload = [
                    'branch_id' => $branch->id,
                    'departure_id' => $departure->id,
                    'group_id' => $group->id,
                    'name' => $name,
                    'category' => $category,
                    'city' => $city,
                    'address' => $description,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'geofence_radius_meters' => $radius,
                    'description' => $description,
                    'is_active' => true,
                ];

                if ($checkpoint) {
                    $checkpoint->restore();
                    $checkpoint->fill($payload)->save();

                    continue;
                }

                Checkpoint::query()->create($payload);
            }
        }
    }

    /**
     * @param Collection<string, Branch> $branches
     * @param Collection<string, Group> $groups
     */
    private function seedPilgrims(Collection $branches, Collection $groups): void
    {
        foreach ($this->pilgrimData() as $data) {
            $group = $groups[$data['group']];
            $branch = $branches[$group->branch->city] ?? $group->branch;
            $user = $this->pilgrimUser($branch, $data['name'], $data['number'], $data['phone']);

            $pilgrim = Pilgrim::query()->updateOrCreate(
                ['registration_number' => $data['number']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'gender' => $data['gender'],
                    'phone' => $data['phone'],
                    'photo_path' => null,
                    'status' => 'active',
                    'monitoring_status' => 'normal',
                    'address' => $data['notes'],
                ],
            );

            $pilgrim->forceFill([
                'activation_pin_hash' => $this->digest($data['pin']),
                'activation_pin_encrypted' => Crypt::encryptString($data['pin']),
                'activation_pin_created_by' => $group->tourLeader?->user_id,
                'activation_pin_generated_at' => now(),
                'activation_pin_used_at' => null,
            ])->save();

            GroupMember::query()->updateOrCreate(
                ['group_id' => $group->id, 'pilgrim_id' => $pilgrim->id],
                ['joined_at' => now(), 'left_at' => null, 'status' => 'active'],
            );
        }
    }

    private function staffUser(Branch $branch, string $name, string $email, string $phone, MobileRole $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => $branch->id,
                'name' => $name,
                'phone_number' => $phone,
                'photo_path' => null,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
        $user->syncRoles($role->value);

        return $user;
    }

    private function pilgrimUser(Branch $branch, string $name, string $registrationNumber, string $phone): User
    {
        $email = strtolower(str_replace('-', '', $registrationNumber)).'@activation.mantauumroh.local';
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => $branch->id,
                'name' => $name,
                'phone_number' => $phone,
                'photo_path' => null,
                'password' => Hash::make(str()->password(32)),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
        $user->syncRoles(MobileRole::Pilgrim->value);

        return $user;
    }

    private function digest(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    /**
     * @return list<array{name: string, number: string, phone: string, gender: string, group: string, pin: string, notes: string}>
     */
    private function pilgrimData(): array
    {
        return [
            ['name' => 'Ahmad Fauzan', 'number' => 'JMH-250001', 'phone' => '081234567801', 'gender' => 'male', 'group' => 'Al Hijrah 01', 'pin' => '458921', 'notes' => 'Lansia, membutuhkan perhatian saat perjalanan jauh'],
            ['name' => 'Siti Aisyah', 'number' => 'JMH-250002', 'phone' => '081234567802', 'gender' => 'female', 'group' => 'Al Hijrah 01', 'pin' => '125874', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Muhammad Rizki', 'number' => 'JMH-250003', 'phone' => '081234567803', 'gender' => 'male', 'group' => 'Al Hijrah 01', 'pin' => '984215', 'notes' => 'Membawa obat pribadi'],
            ['name' => 'Nurhayati', 'number' => 'JMH-250004', 'phone' => '081234567804', 'gender' => 'female', 'group' => 'Al Hijrah 01', 'pin' => '743812', 'notes' => 'Pendamping suami'],
            ['name' => 'Abdul Rahman', 'number' => 'JMH-250005', 'phone' => '081234567805', 'gender' => 'male', 'group' => 'Al Hijrah 01', 'pin' => '365281', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Dewi Kartika', 'number' => 'JMH-250006', 'phone' => '081234567806', 'gender' => 'female', 'group' => 'Al Hijrah 01', 'pin' => '874153', 'notes' => 'Membutuhkan bantuan kursi roda'],
            ['name' => 'Yusuf Maulana', 'number' => 'JMH-250007', 'phone' => '081234567807', 'gender' => 'male', 'group' => 'Al Hijrah 01', 'pin' => '632548', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Fatimah Zahra', 'number' => 'JMH-250008', 'phone' => '081234567808', 'gender' => 'female', 'group' => 'Al Hijrah 01', 'pin' => '518274', 'notes' => 'Lansia dan perlu didampingi saat kegiatan'],
            ['name' => 'Hendra Saputra', 'number' => 'JMH-250009', 'phone' => '081234567809', 'gender' => 'male', 'group' => 'Al Hijrah 01', 'pin' => '742956', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Rina Marlina', 'number' => 'JMH-250010', 'phone' => '081234567810', 'gender' => 'female', 'group' => 'Al Hijrah 01', 'pin' => '186427', 'notes' => 'Alergi terhadap obat tertentu'],
            ['name' => 'Muhammad Ilham', 'number' => 'JMH-250011', 'phone' => '081234567811', 'gender' => 'male', 'group' => 'Al Hijrah 02', 'pin' => '294618', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Rahmawati', 'number' => 'JMH-250012', 'phone' => '081234567812', 'gender' => 'female', 'group' => 'Al Hijrah 02', 'pin' => '631925', 'notes' => 'Membawa obat tekanan darah'],
            ['name' => 'Zainal Abidin', 'number' => 'JMH-250013', 'phone' => '081234567813', 'gender' => 'male', 'group' => 'Al Hijrah 02', 'pin' => '475312', 'notes' => 'Lansia, perlu pengawasan petugas'],
            ['name' => 'Nor Azizah', 'number' => 'JMH-250014', 'phone' => '081234567814', 'gender' => 'female', 'group' => 'Al Hijrah 02', 'pin' => '852761', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'M. Ridwan', 'number' => 'JMH-250015', 'phone' => '081234567815', 'gender' => 'male', 'group' => 'Al Hijrah 02', 'pin' => '397524', 'notes' => 'Pendamping orang tua'],
            ['name' => 'Siti Khadijah', 'number' => 'JMH-250016', 'phone' => '081234567816', 'gender' => 'female', 'group' => 'Al Hijrah 02', 'pin' => '916248', 'notes' => 'Memiliki riwayat hipertensi ringan'],
            ['name' => 'Ahmad Zaini', 'number' => 'JMH-250017', 'phone' => '081234567817', 'gender' => 'male', 'group' => 'Al Hijrah 02', 'pin' => '524879', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Nurul Hidayah', 'number' => 'JMH-250018', 'phone' => '081234567818', 'gender' => 'female', 'group' => 'Al Hijrah 02', 'pin' => '748315', 'notes' => 'Pendamping ibu'],
            ['name' => 'Muhammad Hafiz', 'number' => 'JMH-250019', 'phone' => '081234567819', 'gender' => 'male', 'group' => 'Al Hijrah 02', 'pin' => '281643', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Hj. Salmah', 'number' => 'JMH-250020', 'phone' => '081234567820', 'gender' => 'female', 'group' => 'Al Hijrah 02', 'pin' => '653197', 'notes' => 'Lansia dan membutuhkan bantuan saat berjalan jauh'],
            ['name' => 'Ahmad Syauqi', 'number' => 'JMH-250021', 'phone' => '081234567821', 'gender' => 'male', 'group' => 'Al Hijrah 03', 'pin' => '839251', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Maimunah', 'number' => 'JMH-250022', 'phone' => '081234567822', 'gender' => 'female', 'group' => 'Al Hijrah 03', 'pin' => '417685', 'notes' => 'Membawa obat diabetes'],
            ['name' => 'Muhammad Fadli', 'number' => 'JMH-250023', 'phone' => '081234567823', 'gender' => 'male', 'group' => 'Al Hijrah 03', 'pin' => '725943', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Siti Rahmah', 'number' => 'JMH-250024', 'phone' => '081234567824', 'gender' => 'female', 'group' => 'Al Hijrah 03', 'pin' => '368152', 'notes' => 'Pendamping suami'],
            ['name' => 'Abdul Hakim', 'number' => 'JMH-250025', 'phone' => '081234567825', 'gender' => 'male', 'group' => 'Al Hijrah 03', 'pin' => '951374', 'notes' => 'Lansia, membutuhkan waktu istirahat lebih sering'],
            ['name' => 'Norhasanah', 'number' => 'JMH-250026', 'phone' => '081234567826', 'gender' => 'female', 'group' => 'Al Hijrah 03', 'pin' => '586421', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Rahmat Hidayat', 'number' => 'JMH-250027', 'phone' => '081234567827', 'gender' => 'male', 'group' => 'Al Hijrah 03', 'pin' => '214796', 'notes' => 'Pendamping orang tua'],
            ['name' => 'Siti Mariam', 'number' => 'JMH-250028', 'phone' => '081234567828', 'gender' => 'female', 'group' => 'Al Hijrah 03', 'pin' => '674832', 'notes' => 'Memiliki riwayat asma ringan'],
            ['name' => 'Muhammad Akbar', 'number' => 'JMH-250029', 'phone' => '081234567829', 'gender' => 'male', 'group' => 'Al Hijrah 03', 'pin' => '439618', 'notes' => 'Tidak ada catatan khusus'],
            ['name' => 'Halimah', 'number' => 'JMH-250030', 'phone' => '081234567830', 'gender' => 'female', 'group' => 'Al Hijrah 03', 'pin' => '812547', 'notes' => 'Lansia dan perlu didampingi selama perjalanan'],
        ];
    }
}
