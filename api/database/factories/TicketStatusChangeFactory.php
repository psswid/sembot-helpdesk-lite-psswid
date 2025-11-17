<?php

namespace Database\Factories;

use App\Models\TicketStatusChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatusChange>
 */
class TicketStatusChangeFactory extends Factory
{
    protected $model = TicketStatusChange::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pairs = [
            ['open', 'in_progress'],
            ['in_progress', 'resolved'],
            ['in_progress', 'closed'],
            ['open', 'resolved'],
            ['open', 'closed'],
        ];

        [$old, $new] = $this->faker->randomElement($pairs);

        return [
            'ticket_id' => null,
            'old_status' => $old,
            'new_status' => $new,
            'changed_by_user_id' => null,
            'changed_at' => now()->subDays($this->faker->numberBetween(0, 15))
                ->subHours($this->faker->numberBetween(0, 23))
                ->subMinutes($this->faker->numberBetween(0, 59)),
        ];
    }
}
