<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExternalUsersEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_users_and_source_remote(): void
    {
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Leanne Graham',
                    'email' => 'leanne@example.com',
                ],
            ], 200),
        ]);

        $res = $this->getJson('/api/external-users')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'email'],
                ],
                'source',
            ]);

        $this->assertSame('remote', $res->json('source'));
        $this->assertCount(1, $res->json('data'));
    }

    public function test_sync_requires_ids_validation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->postJson('/api/external-users/sync', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['ids']);
    }

    public function test_sync_creates_users_from_external_ids(): void
    {
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                [ 'id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com' ],
                [ 'id' => 2, 'name' => 'Bob',   'email' => 'bob@example.com'   ],
            ], 200),
        ]);

        $authUser = User::factory()->create();
        $token = $authUser->createToken('api')->plainTextToken;

        $this->postJson('/api/external-users/sync', [
            'ids' => [1, 2],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated()
          ->assertJson([ 'synced' => 2 ]);

        $this->assertDatabaseHas('users', [ 'email' => 'alice@example.com' ]);
        $this->assertDatabaseHas('users', [ 'email' => 'bob@example.com' ]);
    }

    public function test_index_uses_cache_on_remote_error(): void
    {
        // Prime cache with a successful response
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                [ 'id' => 7, 'name' => 'Cached User', 'email' => 'cached@example.com' ],
            ], 200),
        ]);

        $prime = $this->getJson('/api/external-users')->assertOk();
        $this->assertSame('remote', $prime->json('source'));

        // Next, force remote failure; endpoint should serve cached data
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([], 500),
        ]);

        $res = $this->getJson('/api/external-users')->assertOk();

        // Even though the remote failed, endpoint should return the previously cached payload
        $this->assertCount(1, $res->json('data'));
        $this->assertSame('cached@example.com', $res->json('data.0.email'));
    }
}
