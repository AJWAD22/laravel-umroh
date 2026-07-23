<?php

namespace App\Services;

use App\Enums\MobileRole;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\SosReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MobileGroupAccessService
{
    public function activeGroupForPilgrim(Pilgrim $pilgrim): ?Group
    {
        return Group::query()
            ->whereHas('members', fn (Builder $query) => $query
                ->where('pilgrim_id', $pilgrim->id)
                ->where('status', 'active'))
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    public function groupIdsForStaff(User $user, MobileRole $role): Collection
    {
        $profile = match ($role) {
            MobileRole::TourLeader => $user->tourLeader,
            MobileRole::Muthawwif => $user->muthawwif,
            default => null,
        };

        return $profile?->groups()
            ->where('is_active', true)
            ->whereHas('departure', fn (Builder $query) => $query
                ->whereIn('status', ['scheduled', 'departed'])
                ->where(fn (Builder $dateQuery) => $dateQuery
                    ->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', today())))
            ->pluck('groups.id') ?? collect();
    }

    public function pilgrimsForStaff(User $user, MobileRole $role): Builder
    {
        $groupIds = $this->groupIdsForStaff($user, $role);

        return Pilgrim::query()
            ->whereHas('groupMemberships', fn (Builder $query) => $query
                ->whereIn('group_id', $groupIds)
                ->where('status', 'active'))
            ->distinct();
    }

    public function sosReportsForStaff(User $user, MobileRole $role): Builder
    {
        $groupIds = $this->groupIdsForStaff($user, $role);

        return SosReport::query()
            ->whereIn('group_id', $groupIds)
            ->with([
                'pilgrim.branch:id,name',
                'pilgrim.latestLocation',
                'group:id,name,code',
                'handler:id,name',
            ]);
    }
}
