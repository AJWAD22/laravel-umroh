<?php

namespace App\Services;

use App\Models\LocationHistory;
use App\Models\Pilgrim;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use App\Models\PilgrimLocation;

class TrackingHistoryService
{
    /**
     * @return array<string, mixed>
     */
    public function history(Pilgrim $pilgrim, CarbonImmutable $date): array
    {
        $histories = LocationHistory::query()
    ->where('pilgrim_id', $pilgrim->id)
    ->whereBetween('recorded_at', [
        $date->startOfDay(),
        $date->endOfDay(),
    ])
    ->orderBy('recorded_at')
    ->get();

if ($histories->isEmpty()) {

    $last = PilgrimLocation::where('pilgrim_id', $pilgrim->id)
        ->first();

    if ($last) {

        $histories = collect([$last]);
    }
}

        $points = $histories->values()->map(fn ($history, int $index) => [
            'sequence' => $index + 1,
            'latitude' => (float) $history->latitude,
            'longitude' => (float) $history->longitude,
            'accuracy' => $history->accuracy !== null ? (float) $history->accuracy : null,
            'speed' => $history->speed !== null ? (float) $history->speed : null,
            'battery' => $history->battery_level,
            'recorded_at' => $history->recorded_at->toIso8601String(),
        ]);

        return [
            'pilgrim' => [
                'id' => $pilgrim->id,
                'name' => $pilgrim->full_name,
                'registration_number' => $pilgrim->registration_number,
                'branch' => $pilgrim->branch->name,
            ],
            'date' => $date->toDateString(),
            'points' => $points,
            'summary' => [
                'total_points' => $points->count(),
                'total_distance_km' => round($this->totalDistance($points), 3),
                'started_at' => $points->first()['recorded_at'] ?? null,
                'ended_at' => $points->last()['recorded_at'] ?? null,
            ],
            'source' => 'database',
        ];
    }

    /**
     * Tidak ada batas jarak atau radius; semua pasangan titik berurutan dihitung.
     */
    private function totalDistance(Collection $points): float
    {
        return $points->values()->slice(1)->values()->reduce(function (float $total, array $point, int $index) use ($points) {
            $previous = $points->values()->get($index);

            return $total + $this->haversine(
                $previous['latitude'],
                $previous['longitude'],
                $point['latitude'],
                $point['longitude'],
            );
        }, 0.0);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0088;
        $latitudeDelta = deg2rad($lat2 - $lat1);
        $longitudeDelta = deg2rad($lon2 - $lon1);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
