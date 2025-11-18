<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalApiService
{
    /**
     * Fetch current weather data for a given city using the configured external driver.
     * EPIC5-001 focuses on the `weatherapi` driver.
     *
     * @return array{data: array<string,mixed>|null, meta: array<string,mixed>, error?: string, message?: string}
     */
    public function currentWeather(string $city): array
    {
        $driver = config('services.external.driver');
        if ($driver !== 'weatherapi') {
            return [
                'data' => null,
                'meta' => [
                    'driver' => $driver,
                ],
                'error' => 'unsupported_driver',
                'message' => 'The external API driver is not supported yet.',
            ];
        }

        $baseUrl = (string) config('services.external.base_url');
        $apiKey = trim((string) config('services.external.api_key'));
        $timeout = (int) config('services.external.timeout', 5);

        $correlationId = (string) \Illuminate\Support\Str::uuid();

        try {
            $response = Http::baseUrl($baseUrl)
                ->timeout($timeout)
                ->acceptJson()
                ->retry(1, 150) // light retry for transient network hiccups
                ->get('/current.json', [
                    'q' => $city,
                    'key' => $apiKey,
                ]);

            if ($response->successful()) {
                $payload = $response->json();

                return [
                    'data' => $payload,
                    'meta' => [
                        'driver' => $driver,
                        'correlation_id' => $correlationId,
                        'upstream_status' => $response->status(),
                    ],
                ];
            }

            // Non-2xx -> map to controlled error
            Log::warning('external.weatherapi.non_2xx', [
                'city' => $city,
                'driver' => $driver,
                'status' => $response->status(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'data' => null,
                'meta' => [
                    'driver' => $driver,
                    'correlation_id' => $correlationId,
                    'upstream_status' => $response->status(),
                ],
                'error' => 'upstream_unavailable',
                'message' => 'External weather service returned an error.',
            ];
        } catch (ConnectionException $e) {
            Log::warning('external.weatherapi.timeout', [
                'city' => $city,
                'driver' => $driver,
                'correlation_id' => $correlationId,
                'error' => 'timeout',
                'message' => $e->getMessage(),
            ]);

            return [
                'data' => null,
                'meta' => [
                    'driver' => $driver,
                    'correlation_id' => $correlationId,
                ],
                'error' => 'upstream_timeout',
                'message' => 'The external weather service timed out.',
            ];
        } catch (RequestException $e) {
            Log::error('external.weatherapi.request_exception', [
                'city' => $city,
                'driver' => $driver,
                'correlation_id' => $correlationId,
                'error' => 'request_exception',
                'message' => $e->getMessage(),
            ]);

            return [
                'data' => null,
                'meta' => [
                    'driver' => $driver,
                    'correlation_id' => $correlationId,
                ],
                'error' => 'upstream_failure',
                'message' => 'Failed to communicate with external weather service.',
            ];
        } catch (\Throwable $e) {
            Log::error('external.weatherapi.unexpected_error', [
                'city' => $city,
                'driver' => $driver,
                'correlation_id' => $correlationId,
                'error' => 'unexpected',
                'message' => $e->getMessage(),
            ]);

            return [
                'data' => null,
                'meta' => [
                    'driver' => $driver,
                    'correlation_id' => $correlationId,
                ],
                'error' => 'unexpected_error',
                'message' => 'An unexpected error occurred accessing external weather service.',
            ];
        }
    }
}
