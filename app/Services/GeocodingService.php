<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding via OpenStreetMap Nominatim (free, no API key required).
 *
 * Returns ['latitude' => float, 'longitude' => float] or null.
 * Nominatim usage policy requires a valid User-Agent and max 1 req/sec,
 * so calls are best-effort and never throw.
 */
class GeocodingService
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    /** @return array{latitude: float, longitude: float }|null */
    /** @return array{latitude: float, longitude: float }|null */
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'RuachApp/1.0 (https://ruach.example; contact@ruach.example)',
            ])->timeout(10)->get(self::NOMINATIM_URL, [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 0,
            ]);

            if (!$response->ok()) {
                Log::warning('GeocodingService: Nominatim HTTP ' . $response->status());
                return null;
            }

            $data = $response->json();
            if (empty($data[0]['lat']) || empty($data[0]['lon'])) {
                return null;
            }

            return [
                'latitude' => (float) $data[0]['lat'],
                'longitude' => (float) $data[0]['lon'],
            ];
        } catch (\Throwable $e) {
            Log::warning('GeocodingService failed: ' . $e->getMessage());
            return null;
        }
    }
}
