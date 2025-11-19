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

        // Seed tickets using the dedicated TicketSeeder so templates and placeholders
        // from TicketSeeder are used. Then load the recently-created tickets
        // (by reporter) to create history rows below.
        $this->call(TicketSeeder::class);

        // Fetch the tickets just created by TicketSeeder. TicketSeeder sets
        // reporter_id to the same reporter, so use that to select the seeded rows.
        $tickets = Ticket::where('reporter_id', $reporter?->id)
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

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
