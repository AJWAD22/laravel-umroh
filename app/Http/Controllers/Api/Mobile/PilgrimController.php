<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\SendLocationRequest;
use App\Http\Requests\Api\Mobile\SendSosRequest;
use App\Http\Resources\Mobile\LocationResource;
use App\Http\Resources\Mobile\SosReportResource;
use App\Models\LocationHistory;
use App\Models\Group;
use App\Models\PilgrimLocation;
use App\Services\AdminNotificationService;
use App\Services\GeofenceMonitorService;
use App\Services\MobileGroupAccessService;
use App\Services\SosService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PilgrimController extends Controller
{
    public function __construct(
        private readonly MobileGroupAccessService $access,
        private readonly AdminNotificationService $notifications,
        private readonly GeofenceMonitorService $geofence,
        private readonly SosService $sosService,
    ) {}

    public function sendLocation(SendLocationRequest $request): JsonResponse
    {
        // Endpoint ini dipanggil APK jamaah secara berkala.
        // Tujuannya menyimpan lokasi terakhir dan riwayat lokasi.
        $pilgrim = $request->user()->pilgrim;
        $group = $this->activeJourney($request);
        $data = $request->validated();
        $serverReceivedAt = CarbonImmutable::now();
        $recordedAt = isset($data['recorded_at']) ? CarbonImmutable::parse($data['recorded_at']) : $serverReceivedAt;
        unset($data['recorded_at']);

        [$latest, $history, $isCurrent] = DB::transaction(function () use ($pilgrim, $group, $data, $recordedAt, $serverReceivedAt): array {
            $attributes = [
                ...$data,
                'branch_id' => $pilgrim->branch_id,
                'group_id' => $group?->id,
                'recorded_at' => $recordedAt,
                'device_recorded_at' => $recordedAt,
                'server_received_at' => $serverReceivedAt,
            ];

            // pilgrim_locations hanya menyimpan posisi terbaru untuk Live Map.
            $latest = PilgrimLocation::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->lockForUpdate()
                ->first();

            // Paket lokasi yang terlambat tetap masuk histori, tetapi tidak
            // boleh menimpa snapshot terbaru yang ditampilkan pada Live Map.
            $isCurrent = ! $latest || $recordedAt->gte($latest->recorded_at);
            if ($isCurrent) {
                $latest ??= new PilgrimLocation(['pilgrim_id' => $pilgrim->id]);
                $latest->fill([...$attributes, 'gps_status' => 'online'])->save();
            }

            $lastHistory = LocationHistory::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->latest('recorded_at')
                ->lockForUpdate()
                ->first();

            // Histori tidak menyimpan semua GPS point; cukup simpan jika
            // pergerakan berarti atau jeda waktu cukup jauh.
            $history = null;
            if ($this->shouldStoreLocationHistory($lastHistory, $attributes, $recordedAt)) {
                $history = LocationHistory::query()->create([
                    'pilgrim_id' => $pilgrim->id,
                    ...$attributes,
                ]);
            }

            return [$latest, $history, $isCurrent];
        });

        // Setelah lokasi tersimpan, bandingkan posisi jamaah dengan titik
        // kumpul aktif rombongannya. Jika baru keluar radius, admin dan
        // petugas menerima notifikasi web/FCM tanpa mengirim alert berulang.
        if ($isCurrent) {
            $this->geofence->check(
                $pilgrim,
                $group,
                (float) $latest->latitude,
                (float) $latest->longitude,
            );
        }

        return response()->json([
            'message' => 'Lokasi berhasil disimpan.',
            'latest_location' => new LocationResource($latest),
            'history_saved' => $history !== null,
            'history' => $history ? new LocationResource($history) : null,
        ], 201);
    }

    public function sos(SendSosRequest $request): JsonResponse
    {
        // Endpoint darurat. Jamaah menekan SOS, lalu sistem menyimpan laporan
        // dan mengirim notifikasi ke admin/petugas.
        $pilgrim = $request->user()->pilgrim;
        $group = $this->activeJourney($request);
        $data = $request->validated();

        $report = $this->sosService->createOrReturnActive($pilgrim, $group, $data);

        if ($report->wasRecentlyCreated) {
            // Notifikasi database dan FCM dikirim hanya untuk laporan baru.
            $this->notifications->sos($report);
        }

        return (new SosReportResource($report))
            ->additional([
                'message' => $report->wasRecentlyCreated
                    ? 'SOS berhasil dikirim ke petugas.'
                    : 'SOS sebelumnya masih aktif dan sudah diteruskan ke petugas.',
            ])
            ->response()
            ->setStatusCode($report->wasRecentlyCreated ? 201 : 200);
    }

    public function staffLocations(Request $request): JsonResponse
    {
        $group = $this->activeJourney($request);
        $group?->loadMissing([
            'tourLeader.user.staffLocation',
            'muthawwif.user.staffLocation',
        ]);

        $staff = collect([
            ['role' => 'tour-leader', 'label' => 'Tour Leader', 'profile' => $group?->tourLeader],
            ['role' => 'muthawwif', 'label' => 'Muthawwif', 'profile' => $group?->muthawwif],
        ])->map(function (array $item) use ($request): array {
            $profile = $item['profile'];
            $location = $profile?->user?->staffLocation;

            return [
                'role' => $item['role'],
                'label' => $item['label'],
                'id' => $profile?->id,
                'full_name' => $profile?->full_name,
                'phone' => $profile?->phone,
                'location_available' => $location !== null,
                'location' => $location
                    ? (new LocationResource($location))->resolve($request)
                    : null,
            ];
        })->values();

        return response()->json(['data' => $staff]);
    }

    private function activeJourney(Request $request): Group
    {
        $group = $this->access->activeGroupForPilgrim($request->user()->pilgrim);
        $group?->loadMissing('departure');

        if (! $group?->departure
            || ! in_array($group->departure->status, ['scheduled', 'departed'], true)
            || $group->departure->return_date?->endOfDay()->isPast()) {
            throw ValidationException::withMessages([
                'journey' => ['Perjalanan tidak aktif atau telah selesai. Tracking dan SOS sedang dinonaktifkan.'],
            ]);
        }

        return $group;
    }

    private function shouldStoreLocationHistory(?LocationHistory $lastHistory, array $attributes, CarbonImmutable $recordedAt): bool
    {
        if (! $lastHistory) {
            return true;
        }

        $distanceMeters = $this->distanceMeters(
            (float) $lastHistory->latitude,
            (float) $lastHistory->longitude,
            (float) $attributes['latitude'],
            (float) $attributes['longitude'],
        );

        return $distanceMeters >= 20
            || abs($recordedAt->diffInSeconds($lastHistory->recorded_at)) >= 60;
    }

    private function distanceMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadius = 6_371_000;
        $deltaLatitude = deg2rad($toLatitude - $fromLatitude);
        $deltaLongitude = deg2rad($toLongitude - $fromLongitude);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($deltaLongitude / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

}
