<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            return $this->errorResponse(
                driver: $driver,
                correlationId: Str::uuid()->toString(),
                code: 'unsupported_driver',
                message: 'The external API driver is not supported yet.'
            );
        }

        $baseUrl = (string) config('services.external.base_url');
        $apiKey = trim((string) config('services.external.api_key'));
        $timeout = (int) config('services.external.timeout', 5);
        $cacheTtlSeconds = (int) config('services.external.cache_ttl', 3600);
        $correlationId = Str::uuid()->toString();

        // Derive cache key
        [$cacheKey, $queryParams] = $this->buildCacheKey($driver, ['endpoint' => 'current', 'q' => $city]);

        $cached = $this->getCached($cacheKey, $driver, $city);

        try {
            $response = $this->performUpstreamRequest(
                baseUrl: $baseUrl,
                timeout: $timeout,
                path: '/current.json',
                params: ['q' => $city, 'key' => $apiKey]
            );

            if ($response->successful()) {
                $payload = $response->json();
                $this->storeCache($cacheKey, $driver, $city, $payload, $correlationId, $cacheTtlSeconds);

                return $this->successResponse(
                    data: $payload,
                    driver: $driver,
                    correlationId: $correlationId,
                    upstreamStatus: $response->status(),
                    cached: false,
                    cachedAt: null
                );
            }

            return $this->fallbackOrUpstreamError(
                cached: $cached,
                driver: $driver,
                correlationId: $correlationId,
                upstreamStatus: $response->status(),
                cacheKey: $cacheKey,
                city: $city,
                code: 'upstream_unavailable',
                messageNoCache: 'External weather service returned an error and no cached data available.',
                messageWithCache: 'External weather service returned an error; served cached data.'
            );
        } catch (ConnectionException $e) {
            return $this->fallbackOrUpstreamError(
                cached: $cached,
                driver: $driver,
                correlationId: $correlationId,
                upstreamStatus: null,
                cacheKey: $cacheKey,
                city: $city,
                code: 'upstream_timeout',
                messageNoCache: 'The external weather service timed out and no cached data available.',
                messageWithCache: 'The external weather service timed out; served cached data.'
            );
        } catch (RequestException $e) {
            return $this->fallbackOrUpstreamError(
                cached: $cached,
                driver: $driver,
                correlationId: $correlationId,
                upstreamStatus: null,
                cacheKey: $cacheKey,
                city: $city,
                code: 'upstream_failure',
                messageNoCache: 'Failed to communicate with external weather service and no cached data available.',
                messageWithCache: 'Failed to communicate with external weather service; served cached data.'
            );
        } catch (\Throwable $e) {
            return $this->fallbackOrUpstreamError(
                cached: $cached,
                driver: $driver,
                correlationId: $correlationId,
                upstreamStatus: null,
                cacheKey: $cacheKey,
                city: $city,
                code: 'unexpected_error',
                messageNoCache: 'An unexpected error occurred accessing external weather service and no cached data available.',
                messageWithCache: 'An unexpected error occurred; served cached data.'
            );
        }
    }

    /**
     * Build cache key for driver with query params.
     *
     * @param  array<string,mixed>  $params
     * @return array{0:string,1:array<string,mixed>}
     */
    protected function buildCacheKey(string $driver, array $params): array
    {
        $key = 'external:'.$driver.':'.sha1(json_encode($params));

        return [$key, $params];
    }

    /**
     * Attempt to retrieve cached payload.
     *
     * @return array{payload: mixed, stored_at: string}|null
     */
    protected function getCached(string $cacheKey, string $driver, string $city): ?array
    {
        try {
            $cached = Cache::get($cacheKey);
            Log::info($cached ? 'external.weatherapi.cache.hit' : 'external.weatherapi.cache.miss', [
                'key' => $cacheKey,
                'city' => $city,
                'driver' => $driver,
            ]);

            return $cached;
        } catch (\Throwable $e) {
            Log::warning('external.cache.get_failed', [
                'key' => $cacheKey,
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Persist successful upstream payload.
     *
     * @param  array<string,mixed>  $payload
     */
    protected function storeCache(string $cacheKey, string $driver, string $city, array $payload, string $correlationId, int $ttl): void
    {
        try {
            Cache::put($cacheKey, [
                'stored_at' => now()->toIso8601String(),
                'payload' => $payload,
            ], $ttl);
            Log::info('external.weatherapi.cache.store', [
                'key' => $cacheKey,
                'city' => $city,
                'driver' => $driver,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('external.cache.put_failed', [
                'key' => $cacheKey,
                'driver' => $driver,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    /**
     * Perform upstream HTTP request.
     *
     * @param  array<string,string>  $params
     */
    protected function performUpstreamRequest(string $baseUrl, int $timeout, string $path, array $params)
    {
        return Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->acceptJson()
            ->retry(1, 150)
            ->get($path, $params);
    }

    /**
     * Build success response structure.
     *
     * @param  array<string,mixed>  $data
     * @return array{data: array<string,mixed>, meta: array<string,mixed>}
     */
    protected function successResponse(array $data, string $driver, string $correlationId, ?int $upstreamStatus, bool $cached, ?string $cachedAt): array
    {
        return [
            'data' => $data,
            'meta' => [
                'driver' => $driver,
                'correlation_id' => $correlationId,
                'upstream_status' => $upstreamStatus,
                'cached' => $cached,
                'cached_at' => $cachedAt,
            ],
        ];
    }

    /**
     * Build error response optionally with cached fallback.
     *
     * @return array{data: array<string,mixed>|null, meta: array<string,mixed>, error: string, message: string}
     */
    protected function fallbackOrUpstreamError(?array $cached, string $driver, string $correlationId, ?int $upstreamStatus, string $cacheKey, string $city, string $code, string $messageNoCache, string $messageWithCache): array
    {
        if ($cached) {
            Log::info('external.weatherapi.cache.fallback.'.$code, [
                'key' => $cacheKey,
                'city' => $city,
                'driver' => $driver,
                'correlation_id' => $correlationId,
            ]);

            return [
                'data' => $cached['payload'] ?? null,
                'meta' => [
                    'driver' => $driver,
                    'correlation_id' => $correlationId,
                    'upstream_status' => $upstreamStatus,
                    'cached' => true,
                    'cached_at' => $cached['stored_at'] ?? null,
                    'fallback' => true,
                ],
                'error' => $code,
                'message' => $messageWithCache,
            ];
        }

        return [
            'data' => null,
            'meta' => [
                'driver' => $driver,
                'correlation_id' => $correlationId,
                'upstream_status' => $upstreamStatus,
                'cached' => false,
                'cached_at' => null,
            ],
            'error' => $code,
            'message' => $messageNoCache,
        ];
    }

    /**
     * Build generic error response (no fallback attempt).
     *
     * @return array{data: null, meta: array<string,mixed>, error: string, message: string}
     */
    protected function errorResponse(string $driver, string $correlationId, string $code, string $message): array
    {
        return [
            'data' => null,
            'meta' => [
                'driver' => $driver,
                'correlation_id' => $correlationId,
                'cached' => false,
                'cached_at' => null,
            ],
            'error' => $code,
            'message' => $message,
        ];
    }
}
