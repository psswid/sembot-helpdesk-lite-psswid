<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalData\ExternalDataRequest;
use App\Services\ExternalApiService;
use Illuminate\Http\JsonResponse;

class ExternalDataController extends Controller
{
    /**
     * GET /api/external-data — Proxy to external provider (WeatherAPI currently)
     */
    public function index(ExternalDataRequest $request, ExternalApiService $service): JsonResponse
    {
        $validated = $request->validated();
        $city = (string) ($validated['city'] ?? '');

        $result = $service->currentWeather($city);

        // Map service error codes to HTTP statuses per EPIC5-003
        if (isset($result['error'])) {
            $code = $result['error'];
            $status = match ($code) {
                'upstream_timeout' => 504,
                'upstream_unavailable', 'upstream_failure', 'unexpected_error' => 503,
                default => 500,
            };

            return response()->json([
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? 'unknown_error',
                'message' => $result['message'] ?? 'External data unavailable.',
                'meta' => $result['meta'] ?? [],
            ], $status);
        }

        return response()->json([
            'data' => $result['data'] ?? null,
            'meta' => $result['meta'] ?? [],
        ], 200);
    }
}
