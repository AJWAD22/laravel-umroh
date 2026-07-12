<?php

namespace Database\Seeders;

use App\Enums\MobileRole;
use App\Models\Branch;
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

            $branches = $this->ensureBranches();
            $departures = $this->ensureDepartures($branches);
            $this->ensureHotels($branches, $departures);
            $leaders = $this->seedTourLeaders($branches);
            $muthawwifs = $this->seedMuthawwifs($branches);
            $groups = $this->seedGroups($branches, $departures, $leaders, $muthawwifs);
            $this->seedPilgrims($branches, $groups);
        });

        $this->command?->info('Demo master data Mantau Umroh berhasil disiapkan.');
        foreach ($this->deleted as $table => $count) {
            $this->command?->line("Dihapus {$table}: {$count}");
        }
        $this->command?->line('Data baru: 3 Tour Leader, 3 Muthawwif, 3 Rombongan, 30 Jamaah.');
        $this->command?->line('Password semua Tour Leader dan Muthawwif: password123');
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
            'Banjarbaru' => [
                'code' => 'BJB',
                'name' => 'Cabang Banjarbaru',
                'phone' => '0511-6701002',
                'email' => 'banjarbaru@mantauumroh.id',
                'address' => 'Jl. Panglima Batur, Banjarbaru',
                'city' => 'Banjarbaru',
                'province' => 'Kalimantan Selatan',
            ],
            'Martapura' => [
                'code' => 'MTP',
                'name' => 'Cabang Martapura',
                'phone' => '0511-6701003',
                'email' => 'martapura@mantauumroh.id',
                'address' => 'Jl. Sekumpul Raya, Martapura',
                'city' => 'Martapura',
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
     * @return Collection<string, TourLeader>
     */
    private function seedTourLeaders(Collection $branches): Collection
    {
        return collect([
            'Muhammad Arif' => ['email' => 'arif@mantauumroh.id', 'phone' => '081298761001', 'branch' => 'Banjarmasin', 'number' => 'TL-250001'],
            'Agus Salim' => ['email' => 'agus@mantauumroh.id', 'phone' => '081298761002', 'branch' => 'Banjarbaru', 'number' => 'TL-250002'],
            'Fajar Hidayat' => ['email' => 'fajar@mantauumroh.id', 'phone' => '081298761003', 'branch' => 'Martapura', 'number' => 'TL-250003'],
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
            'Ust. Abdullah' => ['email' => 'abdullah@mantauumroh.id', 'phone' => '081355660001', 'branch' => 'Banjarmasin', 'number' => 'MTF-250001', 'languages' => 'Bahasa Indonesia dan Arab / Makkah dan Masjidil Haram'],
            'Ust. Hasan Basri' => ['email' => 'hasan@mantauumroh.id', 'phone' => '081355660002', 'branch' => 'Banjarbaru', 'number' => 'MTF-250002', 'languages' => 'Bahasa Indonesia dan Arab / Madinah dan Masjid Nabawi'],
            'Ust. Syamsuddin' => ['email' => 'syamsuddin@mantauumroh.id', 'phone' => '081355660003', 'branch' => 'Martapura', 'number' => 'MTF-250003', 'languages' => 'Bahasa Indonesia dan Arab / Miqat, Jeddah, dan Ziarah'],
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
            'Al Hijrah 01' => ['code' => 'DEP-RH-001', 'branch' => 'Banjarmasin', 'program' => 'Umrah Al Hijrah Januari 2027', 'departure' => '2027-01-10', 'return' => '2027-01-22'],
            'Al Hijrah 02' => ['code' => 'DEP-RH-002', 'branch' => 'Banjarbaru', 'program' => 'Umrah Al Hijrah Februari 2027', 'departure' => '2027-02-10', 'return' => '2027-02-22'],
            'Al Hijrah 03' => ['code' => 'DEP-RH-003', 'branch' => 'Martapura', 'program' => 'Umrah Al Hijrah Maret 2027', 'departure' => '2027-03-10', 'return' => '2027-03-22'],
        ])->mapWithKeys(fn (array $data, string $groupName): array => [$groupName => Departure::query()->updateOrCreate(
            ['code' => $data['code']],
            [
                'branch_id' => $branches[$data['branch']]->id,
                'program_name' => $data['program'],
                'departure_date' => $data['departure'],
                'return_date' => $data['return'],
                'departure_airport' => 'BDJ',
                'arrival_airport' => 'JED',
                'quota' => 45,
                'status' => 'scheduled',
            ],
        )]);
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
