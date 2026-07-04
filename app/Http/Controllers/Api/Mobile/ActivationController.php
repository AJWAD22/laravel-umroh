<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MobileRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\ActivationStatusRequest;
use App\Http\Requests\Api\Mobile\ClaimActivationRequest;
use App\Models\MobileActivationSession;
use App\Models\Pilgrim;
use App\Services\MobileActivationService;
use App\Services\MobileGroupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    public function __construct(
        private readonly MobileActivationService $activations,
        private readonly MobileGroupAccessService $access,
    ) {}

    public function claim(ClaimActivationRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->activations->claim($request->validated())]);
    }

    public function status(ActivationStatusRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->activations->status($request->validated())]);
    }

    public function pilgrims(Request $request): JsonResponse
    {
        $pilgrims = $this->access
            ->pilgrimsForStaff($request->user(), MobileRole::TourLeader)
            ->with(['user.mobileDevices' => fn ($query) => $query->whereNull('revoked_at')])
            ->orderBy('full_name')
            ->get()
            ->map(fn (Pilgrim $pilgrim) => [
                'id' => $pilgrim->id,
                'registration_number' => $pilgrim->registration_number,
                'full_name' => $pilgrim->full_name,
                'photo_url' => $pilgrim->photo_path ? asset('storage/'.$pilgrim->photo_path) : null,
                'activation_status' => $pilgrim->user?->mobileDevices->isNotEmpty() ? 'active' : 'not_activated',
                'device_name' => $pilgrim->user?->mobileDevices->first()?->device_name,
                'activation_pin' => $pilgrim->activationPin(),
            ]);

        return response()->json(['data' => $pilgrims]);
    }

    public function pending(Request $request): JsonResponse
    {
        $allowedIds = $this->access
            ->pilgrimsForStaff($request->user(), MobileRole::TourLeader)
            ->select('pilgrims.id');

        $sessions = MobileActivationSession::query()
            ->with('pilgrim:id,registration_number,full_name')
            ->whereIn('pilgrim_id', $allowedIds)
            ->where('status', 'awaiting_approval')
            ->where('expires_at', '>', now())
            ->latest('claimed_at')
            ->get()
            ->map(fn (MobileActivationSession $session) => [
                'public_id' => $session->public_id,
                'pilgrim_id' => $session->pilgrim_id,
                'pilgrim_name' => $session->pilgrim->full_name,
                'registration_number' => $session->pilgrim->registration_number,
                'device_name' => $session->device_name,
                'platform' => $session->platform,
                'claimed_at' => $session->claimed_at?->toIso8601String(),
                'expires_at' => $session->expires_at->toIso8601String(),
            ]);

        return response()->json(['data' => $sessions]);
    }

    public function approve(Request $request, MobileActivationSession $session): JsonResponse
    {
        $session = $this->activations->approve($request->user(), $session);

        return response()->json([
            'message' => 'Perangkat Jamaah disetujui.',
            'data' => ['public_id' => $session->public_id, 'status' => $session->status],
        ]);
    }
}
