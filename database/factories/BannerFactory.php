<?php

namespace Database\Factories;

use App\Enums\BannerScope;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'image_path' => 'banners/demo-banner.jpg',
            'target_url' => fake()->optional()->url(),
            'scope' => fake()->randomElement([BannerScope::Sites, BannerScope::Global]),
            'section' => fake()->randomElement(['home', 'events', 'games']),
            'sort_order' => fake()->numberBetween(0, 100),
            'starts_at' => fake()->optional()->dateTimeBetween('-1 week', '+1 week'),
            'ends_at' => fake()->optional()->dateTimeBetween('+1 week', '+1 month'),
            'is_active' => true,
        ];
    }
}
