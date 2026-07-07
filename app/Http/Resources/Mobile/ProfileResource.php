<?php

namespace App\Http\Resources\Mobile;

use App\Enums\MobileRole;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = collect(MobileRole::cases())->first(fn (MobileRole $item) => $this->hasRole($item->value));
        $profile = match ($role) {
            MobileRole::Pilgrim => $this->pilgrim,
            MobileRole::TourLeader => $this->tourLeader,
            MobileRole::Muthawwif => $this->muthawwif,
            default => null,
        };
        $journey = null;

        if ($role === MobileRole::Pilgrim && $profile) {
            $group = $profile->groups()
                ->where('groups.is_active', true)
                ->wherePivot('status', 'active')
                ->with(['departure', 'tourLeader', 'muthawwif'])
                ->latest('groups.id')
                ->first();

            if ($group) {
                $journey = $this->journeyFromGroup($group);
            }
        } elseif (in_array($role, [MobileRole::TourLeader, MobileRole::Muthawwif], true) && $profile) {
            $group = $profile->groups()
                ->where('groups.is_active', true)
                ->with(['departure', 'tourLeader', 'muthawwif'])
                ->latest('groups.id')
                ->first();

            if ($group) {
                $journey = $this->journeyFromGroup($group);
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $role?->value,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ] : null,
            'profile' => $profile ? [
                'id' => $profile->id,
                'number' => $profile->registration_number ?? $profile->employee_number,
                'full_name' => $profile->full_name,
                'phone' => $profile->phone,
                'photo_url' => $profile->photo_path ? asset('storage/'.$profile->photo_path) : null,
                'monitoring_status' => $role === MobileRole::Pilgrim
                    ? $profile->monitoring_status
                    : null,
            ] : null,
            'journey' => $journey,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function journeyFromGroup(Group $group): array
    {
        return [
            'group_name' => $group->name,
            'group_code' => $group->code,
            'program_name' => $group->departure->program_name,
            'departure_date' => $group->departure->departure_date?->toDateString(),
            'return_date' => $group->departure->return_date?->toDateString(),
            'departure_airport' => $group->departure->departure_airport,
            'arrival_airport' => $group->departure->arrival_airport,
            'status' => $group->departure->status,
            'tour_leader_name' => $group->tourLeader?->full_name,
            'muthawwif_name' => $group->muthawwif?->full_name,
        ];
    }
}
