<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExternalDataEndpointTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // Force in-memory sqlite for feature tests to avoid external DB
        Config::set('services.external.driver', 'weatherapi');
        Config::set('services.external.base_url', 'https://api.weatherapi.com/v1');
        Config::set('services.external.api_key', 'test_key');
        Config::set('services.external.timeout', 2);
        Config::set('services.external.cache_ttl', 3600);
        // Persist cache across requests for fallback testing
        Config::set('cache.default', 'file');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/external-data?city=San%20Diego')->assertUnauthorized();
    }

    public function test_validation_requires_city(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $this->getJson('/api/external-data')->assertStatus(422)
            ->assertJsonValidationErrors(['city']);
    }

    public function test_success_returns_200_with_payload_and_meta(): void
    {
        Sanctum::actingAs(User::factory()->make());

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/current.json')) {
                return Http::response([
                    'location' => ['name' => 'San Diego'],
                    'current' => ['temp_c' => 13.3],
                ], 200);
            }

            return Http::response([], 404);
        });

        $this->getJson('/api/external-data?city=San%20Diego')->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'location' => ['name'],
                    'current' => ['temp_c'],
                ],
                'meta' => ['driver', 'correlation_id', 'cached', 'cached_at'],
            ])
            ->assertJsonPath('data.location.name', 'San Diego')
            ->assertJsonPath('meta.driver', 'weatherapi');
    }

    public function test_timeout_fallback_returns_504_with_cached_data(): void
    {
        Sanctum::actingAs(User::factory()->make());

        // Prime cache with success
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/current.json')) {
                return Http::response([
                    'location' => ['name' => 'San Diego'],
                    'current' => ['temp_c' => 10.0],
                ], 200);
            }

            return Http::response([], 404);
        });
        $this->getJson('/api/external-data?city=San%20Diego')->assertOk();

        // Now timeout
        Http::fake([
            'api.weatherapi.com/v1/current.json*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

                $this->getJson('/api/external-data?city=San%20Diego')->assertStatus(504)
            ->assertJsonPath('error', 'upstream_timeout')
            ->assertJsonPath('meta.cached', true)
            ->assertJsonPath('data.location.name', 'San Diego');
    }

    public function test_non2xx_returns_cached_or_error_structure(): void
    {
        Sanctum::actingAs(User::factory()->make());

        // Prime cache
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/current.json')) {
                return Http::response([
                    'location' => ['name' => 'San Diego'],
                    'current' => ['temp_c' => 9.5],
                ], 200);
            }

            return Http::response([], 404);
        });
        $this->getJson('/api/external-data?city=San%20Diego')->assertOk();

        // Now 500
        Http::fake(function ($request) {
            return Http::response([], 500);
        });

                $res = $this->getJson('/api/external-data?city=San%20Diego');
                $this->assertTrue(in_array($res->getStatusCode(), [503, 200], true));
                $res->assertJsonStructure([
                        'data' => [],
                        'meta' => ['driver', 'correlation_id'],
                ]);
    }
}
