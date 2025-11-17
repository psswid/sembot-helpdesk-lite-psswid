<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('agent', 'web');
        Role::findOrCreate('reporter', 'web');
    }

    public function test_index_visibility_and_filters(): void
    {
        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);

        $reporter1 = User::factory()->create();
        $reporter1->syncRoles(['reporter']);

        $reporter2 = User::factory()->create();
        $reporter2->syncRoles(['reporter']);

        // Tickets: two for reporter1 (one matching filters), one for reporter2
        $tMatch = Ticket::factory()->create([
            'reporter_id' => $reporter1->id,
            'priority' => 'high',
            'status' => 'open',
            'tags' => ['bug', 'urgent'],
        ]);

        Ticket::factory()->create([
            'reporter_id' => $reporter1->id,
            'priority' => 'low',
            'status' => 'open',
            'tags' => ['docs'],
        ]);

        Ticket::factory()->create([
            'reporter_id' => $reporter2->id,
            'priority' => 'high',
            'status' => 'open',
            'tags' => ['bug', 'urgent'],
        ]);

        $token = $reporter1->createToken('api')->plainTextToken;

        $res = $this->getJson('/api/tickets?priority=high&status=open', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        // Reporter should only see their own matching ticket
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertSame([$tMatch->id], $ids);

        // Agent can query with same filters (sanity check returns at least 1)
        $agentToken = $agent->createToken('api')->plainTextToken;
        $resAgent = $this->getJson('/api/tickets?priority=high&status=open', [
            'Authorization' => 'Bearer '.$agentToken,
        ])->assertOk();
        $this->assertGreaterThanOrEqual(1, count($resAgent->json('data')));
    }

    public function test_store_sets_reporter_and_defaults(): void
    {
        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);
        $token = $reporter->createToken('api')->plainTextToken;

        $payload = [
            'title' => 'New issue',
            'description' => 'Something is wrong',
            'priority' => 'medium',
            'tags' => ['bug'],
        ];

        $res = $this->postJson('/api/tickets', $payload, [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

    $id = $res->json('data.id');

        $this->assertDatabaseHas('tickets', [
            'id' => $id,
            'reporter_id' => $reporter->id,
            'status' => 'open',
        ]);
    }

    public function test_show_returns_relations_and_history(): void
    {
        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);
        $agentToken = $agent->createToken('api')->plainTextToken;

        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);

        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
            'status' => 'open',
        ]);

        // Two status changes in order
        TicketStatusChange::factory()->create([
            'ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'changed_by_user_id' => $agent->id,
            'changed_at' => now()->subHour(),
        ]);
        TicketStatusChange::factory()->create([
            'ticket_id' => $ticket->id,
            'old_status' => 'in_progress',
            'new_status' => 'resolved',
            'changed_by_user_id' => $agent->id,
            'changed_at' => now(),
        ]);

        $res = $this->getJson("/api/tickets/{$ticket->id}", [
            'Authorization' => 'Bearer '.$agentToken,
        ])->assertOk();

        $res->assertJsonPath('data.reporter.id', $reporter->id);
        $res->assertJsonCount(2, 'data.status_history');
        $this->assertSame('in_progress', $res->json('data.status_history.0.new_status'));
        $this->assertSame('resolved', $res->json('data.status_history.1.new_status'));
        $this->assertSame($agent->id, $res->json('data.status_history.0.changed_by.id'));
    }

    public function test_update_changes_status_and_writes_history(): void
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

        $res = $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $res->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('ticket_status_changes', [
            'ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'changed_by_user_id' => $agent->id,
        ]);
    }

    public function test_destroy_soft_delete_admin_only(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        $adminToken = $admin->createToken('api')->plainTextToken;

        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);
        $reporterToken = $reporter->createToken('api')->plainTextToken;

        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
        ]);

        // Reporter cannot delete
        \Laravel\Sanctum\Sanctum::actingAs($reporter);
        $this->deleteJson("/api/tickets/{$ticket->id}")
            ->assertStatus(403);

        // Admin can delete
        \Laravel\Sanctum\Sanctum::actingAs($admin);
        $this->deleteJson("/api/tickets/{$ticket->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }

    public function test_status_history_endpoint_returns_changes(): void
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

        // Create 3 changes
        TicketStatusChange::factory()->create([
            'ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'changed_by_user_id' => $agent->id,
            'changed_at' => now()->subMinutes(10),
        ]);
        TicketStatusChange::factory()->create([
            'ticket_id' => $ticket->id,
            'old_status' => 'in_progress',
            'new_status' => 'resolved',
            'changed_by_user_id' => $agent->id,
            'changed_at' => now()->subMinutes(5),
        ]);
        TicketStatusChange::factory()->create([
            'ticket_id' => $ticket->id,
            'old_status' => 'resolved',
            'new_status' => 'closed',
            'changed_by_user_id' => $agent->id,
            'changed_at' => now(),
        ]);

        $res = $this->getJson("/api/tickets/{$ticket->id}/status-history", [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertCount(3, $res->json('data'));
        $this->assertSame('in_progress', $res->json('data.0.new_status'));
        $this->assertSame('resolved', $res->json('data.1.new_status'));
        $this->assertSame('closed', $res->json('data.2.new_status'));
        $this->assertSame($agent->id, $res->json('data.0.changed_by.id'));
    }
}
