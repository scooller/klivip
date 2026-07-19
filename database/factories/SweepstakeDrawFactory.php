<?php

namespace Database\Factories;

use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SweepstakeDraw>
 */
class SweepstakeDrawFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sweepstake_id' => Sweepstake::factory(),
            'drawn_by' => User::factory(),
            'winners_count' => $this->faker->numberBetween(1, 5),
            'notes' => $this->faker->optional()->sentence,
            'drawn_at' => now(),
            'notified' => false,
        ];
    }
}
