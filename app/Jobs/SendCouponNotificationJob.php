<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCouponNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public \App\Models\CouponRedemption $redemption
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\PreludeService $preludeService): void
    {
        $this->redemption->loadMissing(['user', 'sweepstake']);

        $user = $this->redemption->user;
        $sweepstake = $this->redemption->sweepstake;
        $couponCount = $this->redemption->coupon_count;

        // 1. Send Email (if user has email)
        if (! empty($user->email)) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    \FinityLabs\FinMail\Mail\TemplateMail::make('coupons-received')
                        ->models([
                            'name' => $user->name ?? 'Usuario',
                            'coupon_count' => $couponCount,
                            'sweepstake_name' => $sweepstake->name,
                        ])
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send coupon email via FinMail', [
                    'redemption_id' => $this->redemption->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Send SMS (if user has phone)
        if (! empty($user->phone)) {
            $message = "Klivip: Acabas de recibir {$couponCount} cupones para el sorteo '{$sweepstake->name}'. Revisa tu cuenta en klivip.test";
            
            try {
                $preludeService->sendSms($user->phone, $message);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send coupon SMS via Prelude', [
                    'redemption_id' => $this->redemption->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
