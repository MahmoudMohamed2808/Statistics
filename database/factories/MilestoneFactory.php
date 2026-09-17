<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'order' => 0,
            'status' => 'not_started',
        ];
    }
}
