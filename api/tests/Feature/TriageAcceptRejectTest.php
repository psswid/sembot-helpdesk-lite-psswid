<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

class TriageAcceptRejectTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_accept_and_update_ticket_and_history(): void
    {
        Role::findOrCreate('agent', 'web');
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $reporter = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
            'priority' => 'low',
            'status' => 'open',
            'tags' => [],
        ]);

        $this->actingAs($agent, 'sanctum');

        $payload = [
            'priority' => 'high',
            'tags' => ['Crash', 'Urgent'],
            'assignee_id' => $agent->id,
            'status' => 'in_progress',
        ];

        $resp = $this->postJson("/api/tickets/{$ticket->id}/triage-accept", $payload);
        $resp->assertOk();

        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority->value ?? (string) $ticket->priority);
        $this->assertEquals('in_progress', $ticket->status->value ?? (string) $ticket->status);
        $this->assertEquals($agent->id, $ticket->assignee_id);

        // Tags should be lowercased and unique
        $this->assertIsArray($ticket->tags);
        $this->assertEqualsCanonicalizing(['crash', 'urgent'], $ticket->tags);

        // History row should be recorded
        $this->assertDatabaseHas('ticket_status_changes', [
            'ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'changed_by_user_id' => $agent->id,
        ]);
    }

    public function test_agent_can_reject_without_changes(): void
    {
        Role::findOrCreate('agent', 'web');
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $reporter = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
            'priority' => 'medium',
            'status' => 'open',
            'tags' => ['general'],
        ]);

        $this->actingAs($agent, 'sanctum');

        $resp = $this->postJson("/api/tickets/{$ticket->id}/triage-reject", [
            'reason' => 'False positive',
        ]);
        $resp->assertNoContent();

        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value ?? (string) $ticket->priority);
        $this->assertEquals('open', $ticket->status->value ?? (string) $ticket->status);

        // Ensure no new history exists for state change to something else
        $this->assertDatabaseMissing('ticket_status_changes', [
            'ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'in_progress',
        ]);
    }

    public function test_reporter_cannot_set_assignee_on_accept(): void
    {
        // Create roles
        Role::findOrCreate('reporter', 'web');
        Role::findOrCreate('agent', 'web');

        // Users
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        $agent = User::factory()->create();
        $agent->assignRole('agent');

        // Ticket owned by reporter
        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
            'priority' => 'low',
            'status' => 'open',
            'assignee_id' => null,
            'tags' => [],
        ]);

        // Act as reporter (authorized to update own ticket by policy)
        $this->actingAs($reporter, 'sanctum');

        $payload = [
            'priority' => 'medium',
            'tags' => ['MyTag'],
            'assignee_id' => $agent->id, // reporter should NOT be able to change this
        ];

    $resp = $this->postJson("/api/tickets/{$ticket->id}/triage-accept", $payload);
    $resp->assertForbidden();

        $ticket->refresh();
        // Assignee must remain unchanged (null) and other fields should be unchanged due to 403
        $this->assertNull($ticket->assignee_id);
        $this->assertEquals('low', $ticket->priority->value ?? (string) $ticket->priority);
        $this->assertSame([], $ticket->tags);
    }
}
