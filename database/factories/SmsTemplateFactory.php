<?php

namespace Database\Factories;

use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsTemplate>
 */
class SmsTemplateFactory extends Factory
{
    public function definition(): array
    {
        $key = $this->faker->unique()->word();

        return [
            'key' => $key,
            'name' => ['es' => $this->faker->words(3, true)],
            'category' => $this->faker->randomElement(['transactional', 'marketing', 'auth']),
            'body' => ['es' => $this->faker->sentence()],
            'token_schema' => null,
            'sender_name' => 'Klivip',
            'is_active' => true,
            'is_locked' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
