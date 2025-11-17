<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use Illuminate\Support\Carbon;
use App\Enums\TicketStatus as TicketStatusEnum;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        // Seed tickets using the factory for realistic variety
        $reporter = User::where('email', 'reporter@example.com')->first() ?: User::query()->first();
        $agent = User::where('email', 'agent@example.com')->first();

        // Create 12 tickets and assign reporter; 70% get an agent assigned
        $tickets = Ticket::factory()
            ->count(12)
            ->state(function () use ($reporter, $agent) {
                $assign = fake()->boolean(70);
                return [
                    'reporter_id' => $reporter?->id,
                    'assignee_id' => $assign && $agent ? $agent->id : null,
                ];
            })
            ->create();

        // Create a few sample history rows for random tickets
        $agentUserId = $agent?->id ?? $reporter?->id;
        if ($agentUserId) {
            $ticketsForHistory = $tickets->shuffle()->take(6);
            foreach ($ticketsForHistory as $ticket) {
                // Ensure final transition aligns with current status when possible
                $final = $ticket->status instanceof TicketStatusEnum
                    ? $ticket->status->value
                    : (string) $ticket->status;

                $old = 'open';
                $new = $final === 'open' ? 'in_progress' : $final;

                // changed_at not later than ticket updated_at
                $changedAt = Carbon::parse($ticket->created_at)->addHours(fake()->numberBetween(1, 72));
                if ($changedAt->gt(Carbon::parse($ticket->updated_at))) {
                    $changedAt = Carbon::parse($ticket->updated_at)->subHour();
                }

                TicketStatusChange::factory()->create([
                    'ticket_id' => $ticket->id,
                    'old_status' => $old,
                    'new_status' => $new,
                    'changed_by_user_id' => $agentUserId,
                    'changed_at' => $changedAt,
                ]);
            }
        }
    }
}
