<?php

namespace Database\Seeders;

use App\Models\AuditLog;
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
use App\Models\PilgrimRegistration;
use App\Models\SosReport;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemoveDemoOperationalDataSeeder extends Seeder
{
    private const PILGRIM_REGISTRATIONS = [
        'JMH-250001', 'JMH-250002', 'JMH-250003', 'JMH-250004', 'JMH-250005',
        'JMH-250006', 'JMH-250007', 'JMH-250008', 'JMH-250009', 'JMH-250010',
        'JMH-250011', 'JMH-250012', 'JMH-250013', 'JMH-250014', 'JMH-250015',
        'JMH-250016', 'JMH-250017', 'JMH-250018', 'JMH-250019', 'JMH-250020',
        'JMH-250021', 'JMH-250022', 'JMH-250023', 'JMH-250024', 'JMH-250025',
        'JMH-250026', 'JMH-250027', 'JMH-250028', 'JMH-250029', 'JMH-250030',
    ];

    private const DEPARTURE_CODES = ['DEP-RH-001', 'DEP-RH-002'];

    private const GROUP_CODES = ['RH-001', 'RH-002'];

    private const CHECKPOINT_NAMES = [
        'Masjidil Haram / Ka\'bah',
        'Masjid Nabawi',
        'Bandara King Abdulaziz Jeddah',
        'Miqat Masjid Aisyah / Tan\'im',
        'Jabal Rahmah',
        'Lobi Al Safwah Tower',
        'Pintu 79 Masjidil Haram',
        'Lobi Dallah Taibah Madinah',
        'Lobi Al Safwah Tower Hemat',
        'Area Bus Ajyad',
        'Lobi Dallah Taibah Madinah Hemat',
    ];

    /** @var array<string, int> */
    private array $deleted = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $pilgrims = Pilgrim::withTrashed()
                ->whereIn('registration_number', self::PILGRIM_REGISTRATIONS)
                ->get();
            $leaders = TourLeader::withTrashed()
                ->where('employee_number', 'TL-250001')
                ->get();
            $muthawwifs = Muthawwif::withTrashed()
                ->where('employee_number', 'MTF-250001')
                ->get();
            $groups = Group::withTrashed()
                ->whereIn('code', self::GROUP_CODES)
                ->get();
            $departures = Departure::withTrashed()
                ->whereIn('code', self::DEPARTURE_CODES)
                ->get();

            $pilgrimIds = $pilgrims->pluck('id');
            $groupIds = $groups->pluck('id');
            $departureIds = $departures->pluck('id');
            $userIds = $pilgrims->pluck('user_id')
                ->merge($leaders->pluck('user_id'))
                ->merge($muthawwifs->pluck('user_id'))
                ->filter()
                ->unique()
                ->values();

            $this->deleted['location histories'] = LocationHistory::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->orWhereIn('group_id', $groupIds)
                ->delete();
            $this->deleted['lokasi jamaah'] = PilgrimLocation::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->delete();
            $this->deleted['laporan SOS'] = SosReport::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->orWhereIn('group_id', $groupIds)
                ->delete();
            $this->deleted['sesi aktivasi'] = MobileActivationSession::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->delete();
            $this->deleted['perangkat mobile'] = MobileDevice::query()
                ->whereIn('user_id', $userIds)
                ->delete();
            $this->deleted['pendaftaran demo'] = PilgrimRegistration::query()
                ->whereIn('user_id', $userIds)
                ->orWhereIn('departure_id', $departureIds)
                ->delete();
            $this->deleted['anggota rombongan'] = GroupMember::query()
                ->whereIn('pilgrim_id', $pilgrimIds)
                ->orWhereIn('group_id', $groupIds)
                ->delete();
            $this->deleted['titik demo'] = Checkpoint::withTrashed()
                ->whereIn('group_id', $groupIds)
                ->orWhereIn('departure_id', $departureIds)
                ->orWhereIn('name', self::CHECKPOINT_NAMES)
                ->forceDelete();

            DB::table('departure_hotel')->whereIn('departure_id', $departureIds)->delete();
            $this->deleted['paket demo'] = Departure::withTrashed()
                ->whereIn('id', $departureIds)
                ->forceDelete();
            $this->deleted['rombongan demo'] = Group::withTrashed()
                ->whereIn('id', $groupIds)
                ->forceDelete();
            $this->deleted['hotel demo'] = Hotel::withTrashed()
                ->whereIn('name', ['Al Safwah Tower Makkah', 'Dallah Taibah Madinah'])
                ->forceDelete();
            $this->deleted['jamaah demo'] = Pilgrim::withTrashed()
                ->whereIn('id', $pilgrimIds)
                ->forceDelete();
            $this->deleted['tour leader demo'] = TourLeader::withTrashed()
                ->whereIn('id', $leaders->pluck('id'))
                ->forceDelete();
            $this->deleted['muthawwif demo'] = Muthawwif::withTrashed()
                ->whereIn('id', $muthawwifs->pluck('id'))
                ->forceDelete();

            AuditLog::query()
                ->whereIn('actor_id', $userIds)
                ->orWhere(function ($query) use ($pilgrimIds, $groupIds): void {
                    $query->where(fn ($nested) => $nested
                        ->where('subject_type', Pilgrim::class)
                        ->whereIn('subject_id', $pilgrimIds))
                        ->orWhere(fn ($nested) => $nested
                            ->where('subject_type', Group::class)
                            ->whereIn('subject_id', $groupIds));
                })
                ->delete();

            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $userIds)
                ->delete();
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $userIds)
                ->delete();
            $this->deleted['akun mobile demo'] = User::query()
                ->whereIn('id', $userIds)
                ->delete();
        });

        $this->command?->info('Data demo operasional telah dihapus.');
        foreach ($this->deleted as $label => $count) {
            $this->command?->line("- {$label}: {$count}");
        }
    }
}
