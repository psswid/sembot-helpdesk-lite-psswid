<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ticket;
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
        Ticket::factory()
            ->count(12)
            ->state(function () use ($reporter, $agent) {
                $assign = fake()->boolean(70);
                return [
                    'reporter_id' => $reporter?->id,
                    'assignee_id' => $assign && $agent ? $agent->id : null,
                ];
            })
            ->create();
    }
}
