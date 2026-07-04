<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\LocationHistoryRequest;
use App\Http\Requests\Api\Mobile\SendLocationRequest;
use App\Http\Requests\Api\Mobile\SendSosRequest;
use App\Http\Resources\Mobile\HotelResource;
use App\Http\Resources\Mobile\LocationResource;
use App\Http\Resources\Mobile\SosReportResource;
use App\Models\LocationHistory;
use App\Models\PilgrimLocation;
use App\Models\SosReport;
use App\Services\MobileGroupAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PilgrimController extends Controller
{
    public function __construct(private readonly MobileGroupAccessService $access) {}

    public function sendLocation(SendLocationRequest $request): JsonResponse
    {
        $pilgrim = $request->user()->pilgrim;
        $group = $this->access->activeGroupForPilgrim($pilgrim);
        $data = $request->validated();
        $recordedAt = isset($data['recorded_at']) ? CarbonImmutable::parse($data['recorded_at']) : now();
        unset($data['recorded_at']);

        [$latest, $history] = DB::transaction(function () use ($pilgrim, $group, $data, $recordedAt): array {
            $attributes = [
                ...$data,
                'group_id' => $group?->id,
                'recorded_at' => $recordedAt,
            ];
            $latest = PilgrimLocation::query()->updateOrCreate(
                ['pilgrim_id' => $pilgrim->id],
                [...$attributes, 'gps_status' => 'online'],
            );
            $history = LocationHistory::query()->create([
                'pilgrim_id' => $pilgrim->id,
                ...$attributes,
            ]);

            return [$latest, $history];
        });

        return response()->json([
            'message' => 'Lokasi berhasil disimpan.',
            'latest_location' => new LocationResource($latest),
            'history' => new LocationResource($history),
        ], 201);
    }

    public function sos(SendSosRequest $request): JsonResponse
    {
        $pilgrim = $request->user()->pilgrim;
        $group = $this->access->activeGroupForPilgrim($pilgrim);
        $data = $request->validated();

        $report = DB::transaction(function () use ($pilgrim, $group, $data): SosReport {
            $report = SosReport::query()->create([
                'branch_id' => $pilgrim->branch_id,
                'pilgrim_id' => $pilgrim->id,
                'group_id' => $group?->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'message' => $data['message'] ?? null,
                'status' => 'active',
                'reported_at' => isset($data['reported_at']) ? CarbonImmutable::parse($data['reported_at']) : now(),
            ]);
            $pilgrim->update(['monitoring_status' => 'sos']);

            return $report;
        });

        return response()->json([
            'message' => 'SOS berhasil dikirim.',
            'data' => new SosReportResource($report->load('pilgrim')),
        ], 201);
    }

    public function hotel(Request $request)
    {
        $group = $this->access->activeGroupForPilgrim($request->user()->pilgrim);
        $hotels = $group?->departure->hotels()->orderByPivot('sequence')->get() ?? collect();

        return HotelResource::collection($hotels);
    }

    public function muthawwifLocation(Request $request): JsonResponse
    {
        $group = $this->access->activeGroupForPilgrim($request->user()->pilgrim);
        $muthawwif = $group?->muthawwif;

        return response()->json([
            'data' => $muthawwif ? [
                'id' => $muthawwif->id,
                'full_name' => $muthawwif->full_name,
                'phone' => $muthawwif->phone,
                'location' => null,
                'location_available' => false,
            ] : null,
        ]);
    }

    public function history(LocationHistoryRequest $request)
    {
        $filters = $request->validated();
        $history = LocationHistory::query()
            ->where('pilgrim_id', $request->user()->pilgrim->id)
            ->whereBetween('recorded_at', [
                CarbonImmutable::parse($filters['date_from'])->startOfDay(),
                CarbonImmutable::parse($filters['date_to'])->endOfDay(),
            ])
            ->latest('recorded_at')
            ->paginate($filters['per_page'] ?? 30);

        return LocationResource::collection($history);
    }
}
