<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketEndpointsNegativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('agent', 'web');
        Role::findOrCreate('reporter', 'web');
    }

    public function test_store_validation_errors_missing_title_and_invalid_priority(): void
    {
        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);
        $token = $reporter->createToken('api')->plainTextToken;

        // Missing title
        $this->postJson('/api/tickets', [
            'priority' => 'high',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['title']);

        // Invalid priority
        $this->postJson('/api/tickets', [
            'title' => 'X',
            'priority' => 'ultra',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['priority']);

        // Tags not an array
        $this->postJson('/api/tickets', [
            'title' => 'Y',
            'priority' => 'low',
            'tags' => 'not-an-array',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['tags']);
    }

    public function test_update_validation_errors_invalid_status_and_assignee(): void
    {
        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);
        $token = $agent->createToken('api')->plainTextToken;

        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);

        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
            'status' => 'open',
        ]);

        // Invalid status
        $this->patchJson('/api/tickets/'.$ticket->id, [
            'status' => 'weird',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);

        // Non-existent assignee_id
        $this->patchJson('/api/tickets/'.$ticket->id, [
            'assignee_id' => 999999,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['assignee_id']);
    }

    public function test_unauthenticated_routes_return_401(): void
    {
        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);
        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
        ]);

        $this->getJson('/api/tickets')->assertUnauthorized();
        $this->postJson('/api/tickets', [])->assertUnauthorized();
        $this->getJson('/api/tickets/'.$ticket->id)->assertUnauthorized();
        $this->patchJson('/api/tickets/'.$ticket->id, [])->assertUnauthorized();
        $this->deleteJson('/api/tickets/'.$ticket->id)->assertUnauthorized();
        $this->getJson('/api/tickets/'.$ticket->id.'/status-history')->assertUnauthorized();
    }

    public function test_reporter_cannot_view_or_update_others_ticket(): void
    {
        $reporter1 = User::factory()->create();
        $reporter1->syncRoles(['reporter']);
        $token1 = $reporter1->createToken('api')->plainTextToken;

        $reporter2 = User::factory()->create();
        $reporter2->syncRoles(['reporter']);

        $othersTicket = Ticket::factory()->create([
            'reporter_id' => $reporter2->id,
        ]);

        // Show forbidden
        $this->getJson('/api/tickets/'.$othersTicket->id, [
            'Authorization' => 'Bearer '.$token1,
        ])->assertStatus(403);

        // Update forbidden
        $this->patchJson('/api/tickets/'.$othersTicket->id, [
            'title' => 'Nope',
        ], [
            'Authorization' => 'Bearer '.$token1,
        ])->assertStatus(403);
    }

    public function test_agent_cannot_delete_ticket(): void
    {
        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);
        $token = $agent->createToken('api')->plainTextToken;

        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);

        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
        ]);

        $this->deleteJson('/api/tickets/'.$ticket->id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403);
    }
}
