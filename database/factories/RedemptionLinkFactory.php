<?php

namespace Database\Factories;

use App\Models\RedemptionSource;
use App\Models\Sweepstake;
use Illuminate\Database\Eloquent\Factories\Factory;

class RedemptionLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sweepstake_id' => Sweepstake::factory()->started(),
            'redemption_source_id' => RedemptionSource::where('code', 'link')->firstOrFail()->id,
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'coupon_count' => $this->faker->numberBetween(1, 20),
            'valid_from' => null,
            'valid_until' => null,
            'max_redemptions' => null,
            'is_active' => true,
            'redemption_count' => 0,
        ];
    }

    public function withPackOf(int $count): self
    {
        return $this->state(fn (array $attributes) => [
            'coupon_count' => $count,
        ]);
    }

    public function withMaxRedemptions(int $max): self
    {
        return $this->state(fn (array $attributes) => [
            'max_redemptions' => $max,
        ]);
    }
}
