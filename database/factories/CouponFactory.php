<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'code' => strtoupper(Str::random(10)),
            'type' => fake()->randomElement([
                CouponType::Percentage,
                CouponType::Fixed,
            ]),
            'value' => fake()->randomElement([10, 15, 20, 50]),
            'max_uses' => fake()->optional()->numberBetween(100, 1000),
            'used_count' => 0,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
