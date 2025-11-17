<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;

class TicketSeeder extends Seeder
{
    /**
     * Seed the tickets table with realistic data.
     */
    public function run(): void
    {
        $faker = FakerFactory::create();

        $reporter = User::where('email', 'reporter@example.com')->first();
        $agent = User::where('email', 'agent@example.com')->first();

        if (! $reporter) {
            // If reporter is missing (unlikely), fall back to any user
            $reporter = User::query()->first();
        }

        $priorities = ['low', 'medium', 'high'];
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $tagPool = ['bug', 'feature', 'ui', 'backend', 'urgent', 'minor', 'docs'];

        $rows = [];
        $count = 12; // 10-15 tickets
        for ($i = 0; $i < $count; $i++) {
            $tags = $faker->randomElements($tagPool, $faker->numberBetween(0, 3));

            $rows[] = [
                'title' => ucfirst($faker->words($faker->numberBetween(3, 6), true)),
                'description' => $faker->paragraphs($faker->numberBetween(1, 3), true),
                'priority' => $faker->randomElement($priorities),
                'status' => $faker->randomElement($statuses),
                'assignee_id' => $faker->boolean(70) && $agent ? $agent->id : null,
                'reporter_id' => $reporter?->id,
                'tags' => $tags ? json_encode(array_values($tags)) : null,
                'created_at' => now()->subDays($faker->numberBetween(0, 20)),
                'updated_at' => now(),
            ];
        }

        DB::table('tickets')->insert($rows);
    }
}
