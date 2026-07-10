<?php

namespace Tests\Feature;

use App\Events\CustomerRegistered;
use App\Models\AutomaticReward;
use App\Models\Sweepstake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AutomaticRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_gets_coupons_on_registration_if_reward_active()
    {
        $sweepstake = Sweepstake::factory()->started()->create([
            'is_active' => true,
            'is_published' => true,
        ]);

        $reward = AutomaticReward::create([
            'name' => 'Registration Bonus',
            'event_type' => 'registration',
            'coupon_amount' => 5,
            'frequency' => 'once_per_user',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        // Fire the event
        event(new CustomerRegistered($user));

        // Check if coupons were generated
        $this->assertDatabaseHas('coupon_redemptions', [
            'automatic_reward_id' => $reward->id,
            'user_id' => $user->id,
            'sweepstake_id' => $sweepstake->id,
            'coupon_count' => 5,
        ]);

        $this->assertDatabaseHas('reward_claims', [
            'automatic_reward_id' => $reward->id,
            'user_id' => $user->id,
        ]);
        
        $this->assertDatabaseCount('sweepstake_coupons', 5);
    }
}
