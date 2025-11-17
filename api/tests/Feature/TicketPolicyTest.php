<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
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

    public function test_reporter_cannot_update_others_ticket(): void
    {
        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);

        $otherReporter = User::factory()->create();
        $otherReporter->syncRoles(['reporter']);

        $othersTicket = Ticket::factory()->create([
            'reporter_id' => $otherReporter->id,
        ]);

        $this->assertFalse($reporter->can('update', $othersTicket));
    }

    public function test_agent_can_update_all_tickets(): void
    {
        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);

        $someone = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $someone->id,
        ]);

        $this->assertTrue($agent->can('update', $ticket));
    }

    public function test_admin_can_delete_any_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);

        $reporter = User::factory()->create();
        $reporter->syncRoles(['reporter']);

        $ticket = Ticket::factory()->create([
            'reporter_id' => $reporter->id,
        ]);

        $this->assertTrue($admin->can('delete', $ticket));
    }

    public function test_visible_to_scope_filters_for_reporter_and_all_for_agent_admin(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);

        $agent = User::factory()->create();
        $agent->syncRoles(['agent']);

        $reporter1 = User::factory()->create();
        $reporter1->syncRoles(['reporter']);

        $reporter2 = User::factory()->create();
        $reporter2->syncRoles(['reporter']);

        // Create tickets
        $t1 = Ticket::factory()->create(['reporter_id' => $reporter1->id]);
        $t2 = Ticket::factory()->create(['reporter_id' => $reporter2->id]);

        // Reporter 1 should only see their own
        $idsForReporter1 = Ticket::query()->visibleTo($reporter1)->pluck('id')->all();
        $this->assertSame([$t1->id], $idsForReporter1);

        // Agent sees all
    $idsForAgent = Ticket::query()->visibleTo($agent)->pluck('id')->sort()->values()->all();
    sort($idsForAgent);
    $expectedAll = [$t1->id, $t2->id];
    sort($expectedAll);
    $this->assertSame($expectedAll, $idsForAgent);

        // Admin sees all
    $idsForAdmin = Ticket::query()->visibleTo($admin)->pluck('id')->sort()->values()->all();
    sort($idsForAdmin);
    $this->assertSame($expectedAll, $idsForAdmin);
    }
}
