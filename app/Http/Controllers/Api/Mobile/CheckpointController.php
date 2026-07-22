<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MobileRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CheckpointResource;
use App\Models\Checkpoint;
use App\Models\Group;
use App\Services\MobileGroupAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CheckpointController extends Controller
{
    public function __construct(private readonly MobileGroupAccessService $access) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $groupIds = collect();
        $departureIds = collect();

        if ($user->hasRole(MobileRole::Pilgrim->value) && $user->pilgrim) {
            $group = $this->access->activeGroupForPilgrim($user->pilgrim);
            if ($group) {
                $groupIds = collect([$group->id]);
                $departureIds = collect([$group->departure_id])->filter();
            }
        }

        if ($user->hasRole(MobileRole::TourLeader->value)) {
            $groupIds = $this->access->groupIdsForStaff($user, MobileRole::TourLeader);
        }

        if ($user->hasRole(MobileRole::Muthawwif->value)) {
            $groupIds = $this->access->groupIdsForStaff($user, MobileRole::Muthawwif);
        }

        if ($groupIds->isNotEmpty() && $departureIds->isEmpty()) {
            $departureIds = Group::query()
                ->whereIn('id', $groupIds)
                ->pluck('departure_id')
                ->filter()
                ->unique()
                ->values();
        }

        $checkpoints = Checkpoint::query()
            ->where('branch_id', $user->branch_id)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($departureIds, $groupIds): void {
                // Urutan cakupan harus tegas:
                // - tanpa departure/group: tujuan umum cabang;
                // - departure saja: seluruh rombongan pada perjalanan itu;
                // - group terisi: hanya rombongan yang dituju.
                // Checkpoint rombongan biasanya juga menyimpan departure_id,
                // sehingga tidak boleh dicocokkan lewat departure OR group.
                $query->where(function (Builder $query): void {
                    $query->whereNull('departure_id')->whereNull('group_id');
                });

                if ($departureIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $query) use ($departureIds): void {
                        $query->whereNull('group_id')
                            ->whereIn('departure_id', $departureIds);
                    });
                }

                if ($groupIds->isNotEmpty()) {
                    $query->orWhereIn('group_id', $groupIds);
                }
            })
            ->orderBy('city')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return CheckpointResource::collection($checkpoints);
    }
}
