<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('agent', 'web');
        Role::findOrCreate('reporter', 'web');
    }

    public function test_reporter_forbidden_on_agent_smoke(): void
    {
        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);

        $token = $reporter->createToken('api')->plainTextToken;

        $this->getJson('/api/tickets/_agent-smoke', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403);
    }

    public function test_agent_allowed_on_agent_smoke(): void
    {
        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);
        $token = $agent->createToken('api')->plainTextToken;

        $this->getJson('/api/tickets/_agent-smoke', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
          ->assertJsonFragment(['ok' => true]);
    }
}
