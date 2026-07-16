<?php

namespace Database\Seeders;

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
use App\Models\StaffLocation;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $branchIds = Branch::withTrashed()->pluck('id');
            $userIds = User::query()->whereNotNull('branch_id')->pluck('id');
            $pilgrimIds = $this->tableExists('pilgrims') ? Pilgrim::withTrashed()->pluck('id') : collect();
            $groupIds = $this->tableExists('groups') ? Group::withTrashed()->pluck('id') : collect();
            $departureIds = $this->tableExists('departures') ? Departure::withTrashed()->pluck('id') : collect();
            $hotelIds = $this->tableExists('hotels') ? Hotel::withTrashed()->pluck('id') : collect();

            if ($userIds->isNotEmpty()) {
                $this->deleteFrom('personal_access_tokens', fn () => DB::table('personal_access_tokens')
                        ->where('tokenable_type', User::class)
                        ->whereIn('tokenable_id', $userIds)
                        ->delete());
                $this->deleteFrom('model_has_roles', fn () => DB::table('model_has_roles')
                        ->where('model_type', User::class)
                        ->whereIn('model_id', $userIds)
                        ->delete());
                $this->deleteFrom('model_has_permissions', fn () => DB::table('model_has_permissions')
                        ->where('model_type', User::class)
                        ->whereIn('model_id', $userIds)
                        ->delete());
                $this->deleteFrom('notifications', fn () => DB::table('notifications')
                        ->where('notifiable_type', User::class)
                        ->whereIn('notifiable_id', $userIds)
                        ->delete());
            }

            if ($pilgrimIds->isNotEmpty()) {
                $this->deleteFrom('pilgrim_locations', fn () => PilgrimLocation::query()->whereIn('pilgrim_id', $pilgrimIds)->delete());
                $this->deleteFrom('location_histories', fn () => LocationHistory::query()->whereIn('pilgrim_id', $pilgrimIds)->delete());
                $this->deleteFrom('sos_reports', fn () => SosReport::query()->whereIn('pilgrim_id', $pilgrimIds)->delete());
                $this->deleteFrom('mobile_activation_sessions', fn () => MobileActivationSession::query()->whereIn('pilgrim_id', $pilgrimIds)->delete());
                $this->deleteFrom('group_members', fn () => GroupMember::query()->whereIn('pilgrim_id', $pilgrimIds)->delete());
            }

            if ($groupIds->isNotEmpty()) {
                $this->deleteFrom('pilgrim_locations', fn () => PilgrimLocation::query()->whereIn('group_id', $groupIds)->delete());
                $this->deleteFrom('location_histories', fn () => LocationHistory::query()->whereIn('group_id', $groupIds)->delete());
                $this->deleteFrom('sos_reports', fn () => SosReport::query()->whereIn('group_id', $groupIds)->delete());
                $this->deleteFrom('group_members', fn () => GroupMember::query()->whereIn('group_id', $groupIds)->delete());
            }

            if ($departureIds->isNotEmpty()) {
                $this->deleteFrom('departure_hotel', fn () => DB::table('departure_hotel')->whereIn('departure_id', $departureIds)->delete());
            }

            if ($hotelIds->isNotEmpty()) {
                $this->deleteFrom('departure_hotel', fn () => DB::table('departure_hotel')->whereIn('hotel_id', $hotelIds)->delete());
            }

            if ($branchIds->isNotEmpty()) {
                $this->deleteFrom('checkpoints', fn () => Checkpoint::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('departures', fn () => Departure::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('hotels', fn () => Hotel::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('groups', fn () => Group::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('tour_leaders', fn () => TourLeader::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('muthawwifs', fn () => Muthawwif::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('pilgrims', fn () => Pilgrim::withTrashed()->whereIn('branch_id', $branchIds)->forceDelete());
                $this->deleteFrom('notifications', fn () => DB::table('notifications')->whereIn('branch_id', $branchIds)->delete());
            }

            if ($userIds->isNotEmpty()) {
                $this->deleteFrom('staff_locations', fn () => StaffLocation::query()->whereIn('user_id', $userIds)->delete());
                $this->deleteFrom('mobile_devices', fn () => MobileDevice::query()->whereIn('user_id', $userIds)->delete());
                User::query()->whereIn('id', $userIds)->delete();
            }

            Branch::withTrashed()->forceDelete();
        });

        $this->command?->info('Data operasional demo berhasil dibersihkan. Super Admin, role/permission, dan pengaturan sistem tetap disimpan.');
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function deleteFrom(string $table, callable $callback): void
    {
        if ($this->tableExists($table)) {
            $callback();
        }
    }
}
