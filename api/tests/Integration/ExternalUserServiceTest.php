<?php

namespace Tests\Integration;

use App\Services\ExternalUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_falls_back_to_cache_on_failure(): void
    {
        $service = app(ExternalUserService::class);

        // First, successful fetch will cache results
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                [ 'id' => 10, 'name' => 'Primed', 'email' => 'primed@example.com' ],
            ], 200),
        ]);
        $first = $service->getUsers();
        $this->assertFalse($service->usedCache());
        $this->assertSame('primed@example.com', $first[0]['email']);

        // Now simulate failure and expect cache to be used
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([], 500),
        ]);
    $second = $service->getUsers();
    // Ensure the cached payload is returned on failure
    $this->assertSame('primed@example.com', $second[0]['email']);
    }

    public function test_service_sync_by_ids_persists_users(): void
    {
        $service = app(ExternalUserService::class);

        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                [ 'id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com' ],
                [ 'id' => 2, 'name' => 'Bob',   'email' => 'bob@example.com'   ],
            ], 200),
        ]);

        $synced = $service->syncByIds([1, 2]);
        $this->assertCount(2, $synced);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'bob@example.com']);
    }
}
