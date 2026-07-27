<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\SosReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SosService
{
    /**
     * @param array{request_id?: string, latitude?: mixed, longitude?: mixed, accuracy?: mixed, message?: string} $data
     */
    public function createOrReturnActive(Pilgrim $pilgrim, ?Group $group, array $data): SosReport
    {
        return DB::transaction(function () use ($pilgrim, $group, $data): SosReport {
            $requestId = $data['request_id'] ?? (string) Str::uuid();

            $idempotentReport = SosReport::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->where('request_id', $requestId)
                ->first();

            if ($idempotentReport) {
                return $idempotentReport->load(['pilgrim.branch', 'pilgrim.latestLocation', 'group', 'handler']);
            }

            $lockedPilgrim = Pilgrim::query()
                ->whereKey($pilgrim->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = SosReport::query()
                ->where('pilgrim_id', $lockedPilgrim->id)
                ->active()
                ->latest('reported_at')
                ->first();

            if ($existing) {
                return $existing->load(['pilgrim.branch', 'pilgrim.latestLocation', 'group', 'handler']);
            }

            [$latitude, $longitude, $accuracy, $locationStatus] = $this->resolveLocation($lockedPilgrim, $data);

            $report = SosReport::query()->create([
                'branch_id' => $lockedPilgrim->branch_id,
                'pilgrim_id' => $lockedPilgrim->id,
                'group_id' => $group?->id,
                'request_id' => $requestId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'location_status' => $locationStatus,
                'message' => $data['message'] ?? 'Jamaah meminta bantuan.',
                'status' => 'new',
                'reported_at' => now(),
            ]);

            $lockedPilgrim->forceFill(['monitoring_status' => 'sos'])->save();

            return $report->load(['pilgrim.branch', 'pilgrim.latestLocation', 'group', 'handler']);
        });
    }

    /**
     * @param array{latitude?: mixed, longitude?: mixed, accuracy?: mixed} $data
     * @return array{0: float|null, 1: float|null, 2: float|null, 3: string}
     */
    private function resolveLocation(Pilgrim $pilgrim, array $data): array
    {
        if (isset($data['latitude'], $data['longitude'])) {
            return [
                (float) $data['latitude'],
                (float) $data['longitude'],
                isset($data['accuracy']) ? (float) $data['accuracy'] : null,
                'available',
            ];
        }

        $latest = PilgrimLocation::query()
            ->where('pilgrim_id', $pilgrim->id)
            ->latest('recorded_at')
            ->first();

        if ($latest) {
            return [
                (float) $latest->latitude,
                (float) $latest->longitude,
                $latest->accuracy !== null ? (float) $latest->accuracy : null,
                'cached',
            ];
        }

        return [null, null, null, 'unavailable'];
    }
}
