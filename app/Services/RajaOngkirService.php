<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RajaOngkirService
{
    private string $apiKey;
    private string $baseUrl;
    private int $originCityId;

    public function __construct()
    {
        $this->apiKey = (string) config('services.rajaongkir.api_key', '');
        $this->baseUrl = (string) config('services.rajaongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
        $this->originCityId = (int) config('services.rajaongkir.origin_city_id', 1);
    }

    /**
     * Get list of provinces from RajaOngkir API (legacy).
     * For Komerce, returns empty array since search is direct to destination.
     */
    public function getProvinces(): array
    {
        // Komerce doesn't require province selection; search destination directly.
        // This method kept for backwards compatibility but returns empty.
        Log::info('getProvinces() called - not used in Komerce API.');
        return [];
    }

    /**
     * Search destinations in Komerce (replaces getCities).
     * Returns array of ['city_id' => string, 'city_name' => string, 'province_id' => string, ...]
     */
    public function searchDestination(string $searchQuery, int $limit = 10, int $offset = 0): array
    {
        if (!$this->apiKey) {
            Log::warning('RajaOngkir API key not configured.');
            return [];
        }

        try {
            $url = $this->baseUrl . '/destination/domestic-destination';

            Log::info('RajaOngkir searchDestination request.', [
                'url' => $url,
                'search' => $searchQuery,
                'limit' => $limit,
                'offset' => $offset,
            ]);

            $response = Http::withHeaders([
                'key' => $this->apiKey,
            ])->timeout(30)->connectTimeout(30)->withoutVerifying()->get(
                    $url,
                    [
                        'search' => $searchQuery,
                        'limit' => $limit,
                        'offset' => $offset,
                    ]
                );

            Log::info('RajaOngkir searchDestination raw response.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::warning('RajaOngkir searchDestination API error.', [
                    'search' => $searchQuery,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            Log::info('RajaOngkir searchDestination parsed JSON.', [
                'keys' => array_keys($data),
                'data_count' => count($data['data'] ?? []),
            ]);

            // Komerce response format: {"meta": {...}, "data": [{"id": ..., "label": ..., ...}]}
            $results = $data['data'] ?? [];

            if (!is_array($results)) {
                Log::warning('RajaOngkir searchDestination - data is not array.', [
                    'data_type' => gettype($results),
                ]);
                return [];
            }

            $destinations = [];
            foreach ($results as $dest) {
                if (!is_array($dest)) {
                    continue;
                }

                // Map Komerce fields to standard RajaOngkir format
                $destinations[] = [
                    'city_id' => (string) ($dest['id'] ?? ''),
                    'city_name' => (string) ($dest['label'] ?? $dest['name'] ?? ''),
                    'province_id' => (string) ($dest['province_id'] ?? ''),
                    'type' => (string) ($dest['type'] ?? ''),
                    'postal_code' => (string) ($dest['postal_code'] ?? ''),
                ];
            }

            Log::info('RajaOngkir searchDestination parsed destinations.', [
                'count' => count($destinations),
            ]);

            return $destinations;
        } catch (Throwable $e) {
            Log::error('RajaOngkir searchDestination exception.', [
                'search' => $searchQuery,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get list of cities in a province (legacy method for backwards compatibility).
     * Now delegates to searchDestination.
     */
    public function getCities(string $provinceId): array
    {
        // For Komerce, use searchDestination instead.
        // This method kept for backwards compatibility but not used.
        Log::info('getCities() called - use searchDestination() instead for Komerce.');
        return [];
    }

    /**
     * Calculate shipping cost for JNE courier using Komerce API.
     * Returns array with 'courier' => 'jne', 'cost' => int, 'etd' => string, or empty array on failure.
     */
    public function calculateCost(string $destinationCityId, int $weight = 1000): array
    {
        $options = $this->calculateCosts($destinationCityId, $weight);

        return $options[0] ?? [];
    }

    /**
     * Calculate shipping cost options for JNE courier using Komerce API.
     * Returns an array of selectable shipping options.
     */
    public function calculateCosts(string $destinationCityId, int $weight = 1000): array
    {
        if (!$this->apiKey) {
            return [];
        }

        try {
            $url = $this->baseUrl . '/calculate/domestic-cost';

            $response = Http::asForm()->withHeaders([
                'key' => $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->timeout(30)->connectTimeout(30)->withoutVerifying()->post($url, [
                        'origin' => $this->originCityId,
                        'destination' => $destinationCityId,
                        'weight' => $weight,
                        'courier' => 'jne',
                        'price' => 'lowest',
                    ]);

            Log::info('RajaOngkir calculateCost raw response.', [
                'url' => $url,
                'destination_city_id' => $destinationCityId,
                'weight' => $weight,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::warning('RajaOngkir calculateCost API error.', [
                    'destination_city_id' => $destinationCityId,
                    'weight' => $weight,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            $results = $data['data'] ?? $data['results'] ?? $data['result'] ?? [];
            if (!is_array($results) || empty($results)) {
                Log::warning('RajaOngkir calculateCost - no result data.', [
                    'response' => $data,
                ]);
                return [];
            }

            $options = [];
            foreach ($results as $result) {
                if (!is_array($result)) {
                    continue;
                }

                $costValue = (int) ($result['cost'] ?? 0);
                $serviceCode = (string) ($result['service'] ?? 'REG');
                $courierCode = (string) ($result['code'] ?? 'jne');
                $etd = (string) ($result['etd'] ?? '');

                if ($costValue <= 0 && isset($result['cost']) && is_array($result['cost'])) {
                    $costValue = (int) ($result['cost'][0]['value'] ?? $result['cost'][0]['price'] ?? 0);
                    $etd = (string) ($result['cost'][0]['etd'] ?? $etd);
                }

                if ($costValue <= 0) {
                    continue;
                }

                $options[] = [
                    'courier' => $courierCode,
                    'service' => $serviceCode,
                    'cost' => $costValue,
                    'etd' => $etd,
                    'name' => (string) ($result['name'] ?? 'JNE'),
                    'description' => (string) ($result['description'] ?? ''),
                ];
            }

            return $options;
        } catch (Throwable $e) {
            Log::error('RajaOngkir calculateCost exception.', [
                'destination_city_id' => $destinationCityId,
                'weight' => $weight,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

