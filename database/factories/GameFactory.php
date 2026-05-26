<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'url' => fake()->url(),
            'description' => fake()->optional()->sentence(),
            'image_path' => fake()->randomElement([
                'games/ruleta.svg',
                'games/blackjack.svg',
                'games/slots.svg',
                'games/poker.svg',
            ]),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
