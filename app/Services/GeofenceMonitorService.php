<?php

namespace App\Services;

use App\Models\Checkpoint;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\Pilgrim;
use Illuminate\Support\Facades\Cache;

class GeofenceMonitorService
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly AdminNotificationService $notifications,
    ) {}

    /**
     * Memeriksa apakah lokasi terbaru jamaah masih berada di dalam radius
     * salah satu titik kumpul aktif milik rombongannya.
     *
     * Notifikasi hanya dikirim saat status berubah dari di dalam menjadi di
     * luar radius. Selama jamaah masih di luar, pengiriman GPS berikutnya
     * tidak akan menghasilkan notifikasi berulang.
     */
    public function check(Pilgrim $pilgrim, ?Group $group, float $latitude, float $longitude): void
    {
        if (! $group) {
            return;
        }

        $checkpoints = Checkpoint::query()
            ->where('branch_id', $pilgrim->branch_id)
            ->where('is_active', true)
            ->whereIn('category', ['titik_kumpul', 'hotel'])
            ->where(function ($query) use ($group): void {
                $query->where(function ($query): void {
                    $query->whereNull('departure_id')->whereNull('group_id');
                });

                if ($group->departure_id) {
                    $query->orWhere(function ($query) use ($group): void {
                        $query->whereNull('group_id')
                            ->where('departure_id', $group->departure_id);
                    });
                }

                $query->orWhere('group_id', $group->id);
            })
            ->get(['id', 'name', 'latitude', 'longitude', 'geofence_radius_meters'])
            ->map(fn (Checkpoint $checkpoint): array => [
                'name' => $checkpoint->name,
                'latitude' => (float) $checkpoint->latitude,
                'longitude' => (float) $checkpoint->longitude,
                'radius' => $checkpoint->geofence_radius_meters,
            ]);

        $hotels = collect();
        if ($group->departure_id) {
            $hotels = Hotel::query()
                ->whereHas('departures', fn ($query) => $query->whereKey($group->departure_id))
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'latitude', 'longitude', 'geofence_radius_meters'])
                ->map(fn (Hotel $hotel): array => [
                    'name' => $hotel->name,
                    'latitude' => (float) $hotel->latitude,
                    'longitude' => (float) $hotel->longitude,
                    'radius' => $hotel->geofence_radius_meters,
                ]);
        }

        $geofences = $checkpoints->concat($hotels)->values();

        $stateKey = "geofence:pilgrim:{$pilgrim->id}:group:{$group->id}";

        if ($geofences->isEmpty()) {
            Cache::forget($stateKey);

            return;
        }

        $defaultRadiusMeters = max(10, (int) $this->settings->get('default_geofence_radius_meters', 250));
        $evaluated = $geofences
            ->map(function (array $geofence) use ($latitude, $longitude, $defaultRadiusMeters): array {
                $radius = max(10, (int) ($geofence['radius'] ?: $defaultRadiusMeters));

                return [
                    'name' => $geofence['name'],
                    'radius' => $radius,
                    'distance' => $this->distanceMeters(
                        $latitude,
                        $longitude,
                        $geofence['latitude'],
                        $geofence['longitude'],
                    ),
                ];
            })
            ->map(fn (array $geofence): array => [
                ...$geofence,
                'is_inside' => $geofence['distance'] <= $geofence['radius'],
            ]);

        $nearest = $evaluated->sortBy('distance')->first();

        if (! $nearest) {
            return;
        }

        $radiusMeters = $nearest['radius'];
        // Jamaah masih aman jika berada di dalam salah satu geofence yang
        // berlaku, walaupun titik terdekat kebetulan mempunyai radius kecil.
        $isOutside = ! $evaluated->contains('is_inside', true);
        $shouldNotify = Cache::lock("{$stateKey}:lock", 5)->get(function () use ($stateKey, $isOutside): bool {
            $previousState = Cache::get($stateKey);
            $currentState = $isOutside ? 'outside' : 'inside';

            Cache::forever($stateKey, $currentState);

            return $isOutside && $previousState !== 'outside';
        });

        if ($shouldNotify === true) {
            $this->notifications->geofenceExit(
                $pilgrim,
                $latitude,
                $longitude,
                $nearest['name'],
                round($nearest['distance'], 1),
                $radiusMeters,
                $group,
            );
        }
    }

    /** Menghitung jarak dua koordinat dengan rumus Haversine. */
    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6_371_000;
        $latitudeDelta = deg2rad($lat2 - $lat1);
        $longitudeDelta = deg2rad($lon2 - $lon1);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
