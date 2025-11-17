<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'user@example.com',
            // password is already hashed to 'password' by factory
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'token',
            ]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_user_when_authenticated(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
        ]);

        // Login to get token
        $login = $this->postJson('/api/login', [
            'email' => 'me@example.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $this->getJson('/api/me', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
          ->assertJsonFragment([
              'email' => 'me@example.com',
          ]);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'logout@example.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        // Logout should revoke token
        $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertNoContent();

        // The token should be removed from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }
}
