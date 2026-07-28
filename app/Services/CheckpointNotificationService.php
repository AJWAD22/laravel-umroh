<?php

namespace App\Services;

use App\Models\Checkpoint;
use App\Models\Pilgrim;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CheckpointNotificationService
{
    public function __construct(private readonly FcmPushService $push) {}

    public function created(Checkpoint $checkpoint): void
    {
        if (! $checkpoint->is_active || $checkpoint->category !== 'titik_kumpul') {
            return;
        }

        $userIds = Pilgrim::query()
            ->whereNotNull('user_id')
            ->whereHas('groupMemberships', function (Builder $query) use ($checkpoint): void {
                $query->where('status', 'active')
                    ->when(
                        $checkpoint->group_id,
                        fn (Builder $groupMemberQuery) => $groupMemberQuery
                            ->where('group_id', $checkpoint->group_id),
                        fn (Builder $groupMemberQuery) => $groupMemberQuery
                            ->whereHas('group', fn (Builder $groupQuery) => $groupQuery
                                ->where('is_active', true)
                                ->where('departure_id', $checkpoint->departure_id)),
                    );
            })
            ->pluck('user_id')
            ->unique();

        if ($userIds->isEmpty()) {
            return;
        }

        $recipients = User::query()
            ->active()
            ->whereIn('id', $userIds)
            ->get();

        $this->push->sendToUsers(
            $recipients,
            'Titik Kumpul Baru',
            "Titik kumpul {$checkpoint->name} telah ditambahkan. Buka aplikasi untuk melihat lokasi.",
            [
                'type' => 'checkpoint_created',
                'checkpoint_id' => $checkpoint->id,
                'group_id' => $checkpoint->group_id,
                'departure_id' => $checkpoint->departure_id,
                'latitude' => $checkpoint->latitude,
                'longitude' => $checkpoint->longitude,
            ],
        );
    }
}
