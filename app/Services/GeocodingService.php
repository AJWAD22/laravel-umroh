<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * @return array<int, array{latitude: float, longitude: float, label: string}>
     */
    public function search(string $query): array
    {
        $normalized = mb_strtolower(trim($query));

        return Cache::remember(
            'geocoding:nominatim:'.sha1($normalized),
            now()->addDays(30),
            function () use ($normalized): array {
                $response = Http::acceptJson()
                    ->withHeaders([
                        'User-Agent' => 'MantauUmroh/1.0 (https://mantauumroh.web.id)',
                    ])
                    ->timeout(8)
                    ->retry(2, 250)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'jsonv2',
                        'limit' => 5,
                        'addressdetails' => 1,
                        'countrycodes' => 'sa,id',
                        'q' => $normalized,
                    ])
                    ->throw();

                return collect($response->json())
                    ->filter(fn ($item): bool => is_array($item)
                        && is_numeric($item['lat'] ?? null)
                        && is_numeric($item['lon'] ?? null))
                    ->map(fn (array $item): array => [
                        'latitude' => (float) $item['lat'],
                        'longitude' => (float) $item['lon'],
                        'label' => (string) ($item['display_name'] ?? $normalized),
                    ])
                    ->values()
                    ->all();
            },
        );
    }
}
