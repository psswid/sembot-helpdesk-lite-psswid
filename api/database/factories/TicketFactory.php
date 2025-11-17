<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tagPool = ['bug', 'feature', 'ui', 'backend', 'urgent', 'minor', 'docs'];
        $tags = $this->faker->randomElements($tagPool, $this->faker->numberBetween(0, 3));

        return [
            'title' => ucfirst($this->faker->words($this->faker->numberBetween(3, 6), true)),
            'description' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
            'priority' => $this->faker->randomElement([
                TicketPriority::Low->value,
                TicketPriority::Medium->value,
                TicketPriority::High->value,
            ]),
            'status' => $this->faker->randomElement([
                TicketStatus::Open->value,
                TicketStatus::InProgress->value,
                TicketStatus::Resolved->value,
                TicketStatus::Closed->value,
            ]),
            'assignee_id' => null,
            'reporter_id' => null,
            'tags' => $tags ?: null,
            'created_at' => now()->subDays($this->faker->numberBetween(0, 20)),
            'updated_at' => now(),
        ];
    }
}
