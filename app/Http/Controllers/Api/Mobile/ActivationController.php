<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\ActivationStatusRequest;
use App\Http\Requests\Api\Mobile\ClaimActivationRequest;
use App\Services\MobileActivationService;
use Illuminate\Http\JsonResponse;

class ActivationController extends Controller
{
    public function __construct(private readonly MobileActivationService $activations) {}

    public function claim(ClaimActivationRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->activations->claim($request->validated())]);
    }

    public function status(ActivationStatusRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->activations->status($request->validated())]);
    }

}
