<?php

namespace Tests\Unit;

use App\Services\ExternalApiService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure driver and base config for tests
        Config::set('services.external.driver', 'weatherapi');
        Config::set('services.external.base_url', 'https://api.weatherapi.com/v1');
        Config::set('services.external.api_key', 'test_key');
        Config::set('services.external.timeout', 2);
        Config::set('services.external.cache_ttl', 3600);
    }

    public function test_success_stores_cache_entry(): void
    {
        $service = app(ExternalApiService::class);

        // Prime cache with a successful response
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/current.json')) {
                return Http::response([
                    'location' => ['name' => 'San Diego'],
                    'current' => ['temp_c' => 13.3],
                ], 200);
            }
            return Http::response([], 404);
        });
        $first = $service->currentWeather('San Diego');
        $this->assertSame('San Diego', $first['data']['location']['name'] ?? null);
        $this->assertFalse($first['meta']['cached']);

        // Verify cache entry exists with expected key
        $driver = config('services.external.driver');
        $key = 'external:'.$driver.':'.sha1(json_encode(['endpoint' => 'current', 'q' => 'San Diego']));

        $this->assertNotNull(cache()->get($key));
    }

    public function test_success_then_timeout_falls_back_to_cache(): void
    {
        $service = app(ExternalApiService::class);

        // Prime cache
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/current.json')) {
                return Http::response([
                    'location' => ['name' => 'San Diego'],
                    'current' => ['temp_c' => 11.0],
                ], 200);
            }
            return Http::response([], 404);
        });
        $primed = $service->currentWeather('San Diego');
        $this->assertSame('San Diego', $primed['data']['location']['name'] ?? null);

        // Simulate connection timeout
        Http::fake([
            'api.weatherapi.com/v1/current.json*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);
        $fallback = $service->currentWeather('San Diego');
        $this->assertSame('San Diego', $fallback['data']['location']['name'] ?? null);
        $this->assertTrue($fallback['meta']['cached']);
        $this->assertTrue($fallback['meta']['fallback']);
        $this->assertSame('upstream_timeout', $fallback['error']);
    }

    public function test_failure_without_cache_returns_error(): void
    {
        $service = app(ExternalApiService::class);

        Http::fake([
            'api.weatherapi.com/v1/current.json*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $result = $service->currentWeather('San Diego');
        $this->assertNull($result['data']);
        $this->assertFalse($result['meta']['cached']);
        $this->assertSame('upstream_timeout', $result['error']);
    }
}
