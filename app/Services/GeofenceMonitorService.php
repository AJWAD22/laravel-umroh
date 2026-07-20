<?php

namespace App\Services;

use App\Models\Checkpoint;
use App\Models\Group;
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
            ->where('group_id', $group->id)
            ->where('category', 'titik_kumpul')
            ->where('is_active', true)
            ->get(['id', 'name', 'latitude', 'longitude']);

        $stateKey = "geofence:pilgrim:{$pilgrim->id}:group:{$group->id}";

        if ($checkpoints->isEmpty()) {
            Cache::forget($stateKey);

            return;
        }

        $radiusMeters = max(10, (int) $this->settings->get('default_geofence_radius_meters', 250));
        $nearest = $checkpoints
            ->map(function (Checkpoint $checkpoint) use ($latitude, $longitude): array {
                return [
                    'checkpoint' => $checkpoint,
                    'distance' => $this->distanceMeters(
                        $latitude,
                        $longitude,
                        (float) $checkpoint->latitude,
                        (float) $checkpoint->longitude,
                    ),
                ];
            })
            ->sortBy('distance')
            ->first();

        if (! $nearest) {
            return;
        }

        $isOutside = $nearest['distance'] > $radiusMeters;
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
                $nearest['checkpoint']->name,
                round($nearest['distance'], 1),
                $radiusMeters,
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
