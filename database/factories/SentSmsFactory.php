<?php

namespace Database\Factories;

use App\Enums\SmsStatus;
use App\Models\SentSms;
use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SentSms>
 */
class SentSmsFactory extends Factory
{
    public function definition(): array
    {
        $status = $this->faker->randomElement(SmsStatus::cases());

        return [
            'sms_template_id' => SmsTemplate::factory(),
            'to' => $this->faker->numerify('+############'),
            'from' => 'Klivip',
            'subject' => $this->faker->optional()->words(2, true),
            'body' => $this->faker->sentence(),
            'status' => $status,
            'sent_at' => $status === SmsStatus::Sent ? $this->faker->dateTimeThisMonth() : null,
            'metadata' => null,
            'error_message' => $status === SmsStatus::Failed ? $this->faker->sentence() : null,
            'sent_by' => null,
        ];
    }
}
