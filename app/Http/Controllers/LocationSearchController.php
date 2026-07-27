<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationSearchRequest;
use App\Services\GeocodingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class LocationSearchController extends Controller
{
    public function __invoke(
        LocationSearchRequest $request,
        GeocodingService $geocoding,
    ): JsonResponse {
        try {
            $results = $geocoding->search($request->string('q')->toString());
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Layanan pencarian lokasi sedang tidak tersedia. Pilih titik langsung pada peta.',
            ], 502);
        }

        return response()->json(['data' => $results]);
    }
}
