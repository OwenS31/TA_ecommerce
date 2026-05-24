<?php

namespace App\Http\Controllers\Customer;

use App\Services\RajaOngkirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShippingController extends BaseCustomerController
{
    public function shippingProvinces(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Use shippingSearchDestination instead for Komerce API.',
            'data' => [],
        ]);
    }

    public function shippingSearchDestination(Request $request): JsonResponse
    {
        $searchQuery = $request->input('search', '');
        $limit = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);

        if (strlen($searchQuery) < 2) {
            return response()->json([
                'message' => 'Search query minimal 2 karakter.',
            ], 422);
        }

        try {
            $service = new RajaOngkirService();
            $destinations = $service->searchDestination($searchQuery, $limit, $offset);

            Log::info('shippingSearchDestination controller result.', [
                'search' => $searchQuery,
                'destinations_count' => count($destinations),
                'destinations' => $destinations,
            ]);

            return response()->json([
                'status' => 'ok',
                'data' => $destinations,
                'message' => empty($destinations) ? 'Tidak ada hasil pencarian kota.' : null,
            ]);
        } catch (Throwable $e) {
            Log::error('Error searching shipping destination.', [
                'search' => $searchQuery,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error mencari kota.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function shippingCities(Request $request): JsonResponse
    {
        $searchQuery = $request->input('search', '');

        if (empty($searchQuery)) {
            return response()->json([
                'message' => 'search parameter diperlukan.',
            ], 422);
        }

        try {
            $service = new RajaOngkirService();
            $destinations = $service->searchDestination($searchQuery, 10, 0);

            if (empty($destinations)) {
                return response()->json([
                    'message' => 'Gagal mengambil data kota dari RajaOngkir.',
                ], 503);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $destinations,
            ]);
        } catch (Throwable $e) {
            Log::error('Error fetching shipping cities.', [
                'search' => $searchQuery,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error mengambil data kota.',
            ], 500);
        }
    }

    public function shippingCalculate(Request $request): JsonResponse
    {
        $destinationCityId = $request->input('destination_city_id');
        $weight = (int) $request->input('weight', 1000);

        if (!$destinationCityId) {
            return response()->json([
                'message' => 'destination_city_id diperlukan.',
            ], 422);
        }

        try {
            $service = new RajaOngkirService();
            $costOptions = $service->calculateCosts((string) $destinationCityId, $weight);

            if (empty($costOptions)) {
                return response()->json([
                    'message' => 'Gagal menghitung ongkir. Coba beberapa saat lagi.',
                ], 503);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $costOptions,
            ]);
        } catch (Throwable $e) {
            Log::error('Error calculating shipping cost.', [
                'destination_city_id' => $destinationCityId,
                'weight' => $weight,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error menghitung ongkir.',
            ], 500);
        }
    }
}
