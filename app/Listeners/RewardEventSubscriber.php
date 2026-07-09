<?php

namespace App\Listeners;

use App\Events\CustomerAnniversary;
use App\Events\CustomerBirthday;
use App\Events\CustomerProfileUpdated;
use App\Events\CustomerRegistered;
use App\Models\AutomaticReward;
use App\Models\Sweepstake;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class RewardEventSubscriber
{
    public function __construct(protected RedemptionService $redemptionService)
    {
    }

    public function handleUserRegistered(CustomerRegistered $event): void
    {
        $this->processEvent($event->user, 'registration');
    }

    public function handleUserProfileUpdated(CustomerProfileUpdated $event): void
    {
        $this->processEvent($event->user, 'profile_update');
    }

    public function handleUserBirthday(CustomerBirthday $event): void
    {
        $this->processEvent($event->user, 'birthday');
    }

    public function handleUserAnniversary(CustomerAnniversary $event): void
    {
        $this->processEvent($event->user, 'anniversary');
    }

    protected function processEvent(User $user, string $eventType): void
    {
        // Get active rewards for this event type
        $rewards = AutomaticReward::where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        if ($rewards->isEmpty()) {
            return;
        }

        // Get active sweepstake
        $activeSweepstake = Sweepstake::active()->first();

        if (! $activeSweepstake) {
            return;
        }

        foreach ($rewards as $reward) {
            // Check site match
            if ($reward->site_id !== null) {
                // If the user doesn't belong to this site, skip
                if (! $user->sites()->where('site_id', $reward->site_id)->exists()) {
                    continue;
                }
            }

            // Check specific sweepstake match
            if ($reward->sweepstake_id !== null && $reward->sweepstake_id !== $activeSweepstake->id) {
                continue;
            }

            // Check if already claimed based on frequency
            $query = $reward->claims()->where('user_id', $user->id);

            if ($reward->frequency === 'once_per_sweepstake') {
                $query->where('sweepstake_id', $activeSweepstake->id);
            } elseif ($reward->frequency === 'yearly') {
                $query->whereYear('created_at', now()->year);
            } // 'once_per_user' doesn't need extra conditions

            if ($query->exists()) {
                continue; // Already claimed
            }

            // Grant coupons
            try {
                $redemption = $this->redemptionService->grantAutomaticReward($user, $reward, $activeSweepstake);

                if ($redemption) {
                    $reward->claims()->create([
                        'user_id' => $user->id,
                        'sweepstake_id' => $activeSweepstake->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to grant automatic reward', [
                    'user_id' => $user->id,
                    'reward_id' => $reward->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            CustomerRegistered::class,
            [RewardEventSubscriber::class, 'handleUserRegistered']
        );

        $events->listen(
            CustomerProfileUpdated::class,
            [RewardEventSubscriber::class, 'handleUserProfileUpdated']
        );

        $events->listen(
            CustomerBirthday::class,
            [RewardEventSubscriber::class, 'handleUserBirthday']
        );

        $events->listen(
            CustomerAnniversary::class,
            [RewardEventSubscriber::class, 'handleUserAnniversary']
        );
    }
}
