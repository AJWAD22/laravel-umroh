<?php

namespace App\Services;

use App\Models\Pilgrim;
use App\Models\SosReport;
use Illuminate\Support\Facades\DB;

class SosService
{
    public function create(Pilgrim $pilgrim, $group, array $data): array
    {
        return DB::transaction(function () use ($pilgrim, $group, $data) {
            $lockedPilgrim = Pilgrim::query()
                ->lockForUpdate()
                ->findOrFail($pilgrim->id);

            if (!empty($data['request_id'])) {
                $duplicate = SosReport::query()
                    ->where('request_id', $data['request_id'])
                    ->first();

                if ($duplicate) {
                    return ['report' => $duplicate, 'created' => false];
                }
            }

            $existing = SosReport::query()
                ->where('pilgrim_id', $lockedPilgrim->id)
                ->active()
                ->latest('reported_at')
                ->first();

            if ($existing) {
                return ['report' => $existing, 'created' => false];
            }

            $locationAvailable = isset($data['latitude'], $data['longitude']);

            $report = SosReport::create([
                'branch_id' => $lockedPilgrim->branch_id,
                'pilgrim_id' => $lockedPilgrim->id,
                'group_id' => $group?->id,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'message' => $data['message'] ?? 'Jamaah meminta bantuan.',
                'request_id' => $data['request_id'] ?? null,
                'location_status' => $locationAvailable ? 'available' : 'unavailable',
                'status' => 'new',
                'reported_at' => now(),
            ]);

            $lockedPilgrim->forceFill([
                'monitoring_status' => 'sos'
            ])->save();

            return ['report' => $report, 'created' => true];
        });
    }
}
