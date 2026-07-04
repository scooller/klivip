<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SweepstakeFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('+1 day', '+7 days');
        $expiresAt = $this->faker->dateTimeBetween($startsAt, '+30 days');

        return [
            'site_id' => Site::factory(),
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'max_coupons' => $this->faker->numberBetween(10, 1000),
            'max_coupons_per_user' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'is_published' => true,
            'last_coupon_number' => 0,
            'prize_description' => $this->faker->sentence,
        ];
    }

    public function withNoLimit(): self
    {
        return $this->state(fn (array $attributes) => [
            'max_coupons' => null,
            'max_coupons_per_user' => null,
        ]);
    }

    public function started(): self
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
