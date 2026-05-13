<?php

namespace Database\Factories;

use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use App\Models\Promotion;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
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
            'title' => fake()->sentence(3),
            'offer_label' => fake()->randomElement(['10% OFF', '2x1', '20% OFF']),
            'description' => fake()->optional()->sentence(),
            'scope' => PromotionScope::Site,
            'schedule_type' => PromotionScheduleType::Standard,
            'recurrent_days' => null,
            'special_date' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'start_time' => null,
            'end_time' => null,
            'is_active' => true,
        ];
    }
}
