<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TriageEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_get_triage_suggestion(): void
    {
        // Use mock driver to avoid external calls
        config(['services.llm.driver' => 'mock']);

        // Ensure roles exist and create an agent user
        Role::findOrCreate('agent', 'web');
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        // Create a ticket
        $reporter = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
            'title' => 'Crash on payment with urgent error',
            'description' => 'Payment flow triggers crash and urgent error during billing.',
        ]);

        // Act as agent via Sanctum guard for API group
        $this->actingAs($agent, 'sanctum');

        $response = $this->postJson("/api/tickets/{$ticket->id}/triage-suggest");

        $response->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('data')
                ->whereType('data.priority', 'string')
                ->whereType('data.tags', 'array')
                ->whereType('data.reasoning', 'string')
                ->whereType('data.confidence', ['integer', 'double'])
            );

        $payload = $response->json('data');
        $this->assertEquals('high', $payload['priority']);
        $this->assertContains('crash', $payload['tags']);
        $this->assertContains('billing', $payload['tags']);
    }
}
