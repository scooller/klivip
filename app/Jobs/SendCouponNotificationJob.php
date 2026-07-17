<?php

namespace App\Jobs;

use App\Models\CouponRedemption;
use App\Services\PreludeService;
use App\Services\SmsService;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCouponNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CouponRedemption $redemption
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PreludeService $preludeService, SmsService $smsService): void
    {
        $this->redemption->loadMissing(['user', 'sweepstake']);

        $user = $this->redemption->user;
        $sweepstake = $this->redemption->sweepstake;
        $couponCount = $this->redemption->coupon_count;

        // 1. Send Email (if user has email)
        if (! empty($user->email)) {
            try {
                Mail::to($user->email)->send(
                    TemplateMail::make('coupons-received')
                        ->models([
                            'name' => $user->name ?? 'Usuario',
                            'coupon_count' => $couponCount,
                            'sweepstake_name' => $sweepstake->name,
                        ])
                );
            } catch (\Exception $e) {
                Log::error('Failed to send coupon email via FinMail', [
                    'redemption_id' => $this->redemption->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Send SMS via template (if user has phone)
        if (! empty($user->phone)) {
            $smsService->sendFromTemplate('coupons-received', $user->phone, [
                'coupon_count' => $couponCount,
                'sweepstake_name' => $sweepstake->name,
            ], [
                'sendable' => $this->redemption,
                'subject' => 'Cupones recibidos',
            ]);
        }
    }
}
