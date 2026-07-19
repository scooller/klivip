<?php

namespace App\Jobs;

use App\Models\SweepstakeCoupon;
use App\Models\SweepstakeDraw;
use App\Services\SmsService;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifySweepstakeWinnersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos antes de descartar el job.
     */
    public int $tries = 3;

    /**
     * Tiempo máximo de ejecución del job.
     */
    public int $timeout = 120;

    public function __construct(
        public SweepstakeDraw $draw
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        $sweepstake = $this->draw->sweepstake;

        if (! $sweepstake) {
            Log::warning('NotifySweepstakeWinnersJob: sweepstake missing for draw', [
                'draw_id' => $this->draw->id,
            ]);

            return;
        }

        $prize = $sweepstake->prize_description;

        $this->draw->winners
            ->filter(fn (SweepstakeCoupon $coupon) => $coupon->relationLoaded('user') ? (bool) $coupon->user : (bool) $coupon->user_id)
            ->each(function (SweepstakeCoupon $coupon) use ($sweepstake, $prize, $smsService): void {
                $user = $coupon->user;
                $position = (int) ($coupon->pivot->position ?? 0);

                if (! $user) {
                    return;
                }

                $tokens = [
                    'name' => $user->name ?? 'Participante',
                    'sweepstake_name' => $sweepstake->name,
                    'prize' => $prize ?? 'el premio del sorteo',
                    'coupon_number' => $coupon->getDisplayNumber(),
                    'position' => $position,
                ];

                // 1. Email (si el usuario tiene email)
                if (! empty($user->email)) {
                    try {
                        Mail::to($user->email)->send(
                            TemplateMail::make('prize-won')
                                ->models($tokens)
                        );
                    } catch (\Throwable $e) {
                        Log::error('Failed to send prize-won email', [
                            'draw_id' => $this->draw->id,
                            'coupon_id' => $coupon->id,
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // 2. SMS (si el usuario tiene teléfono)
                if (! empty($user->phone)) {
                    try {
                        $smsService->sendFromTemplate('prize-won', $user->phone, $tokens, [
                            'sendable' => $coupon,
                            'sent_by' => $this->draw->drawn_by,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to send prize-won SMS', [
                            'draw_id' => $this->draw->id,
                            'coupon_id' => $coupon->id,
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
