<?php

namespace App\Services;

use App\Exceptions\RedemptionException;
use App\Jobs\SendCouponNotificationJob;
use App\Models\AutomaticReward;
use App\Models\CouponRedemption;
use App\Models\RedemptionLink;
use App\Models\Sweepstake;
use App\Models\SweepstakeCoupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedemptionService
{
    /**
     * Procesa un cobro de cupones desde un link/QR
     */
    public function redeem(
        string $linkCode,
        ?string $userEmail = null,
        ?string $userPhone = null,
        ?string $userName = null,
        ?User $authenticatedUser = null,
        array $metadata = []
    ): CouponRedemption {
        $link = RedemptionLink::with(['sweepstake', 'redemptionSource'])
            ->where('code', $linkCode)
            ->firstOrFail();

        if (! $link->isAvailable()) {
            throw RedemptionException::linkNotAvailable();
        }

        $sweepstake = $link->sweepstake;

        if (! $sweepstake->isAvailable()) {
            throw RedemptionException::sweepstakeNotAvailable();
        }

        $user = $authenticatedUser ?? $this->getOrCreateUser($userEmail, $userPhone, $userName);

        if ($sweepstake->hasUserReachedLimit($user, $link->coupon_count)) {
            throw RedemptionException::userLimitReached();
        }

        return DB::transaction(function () use ($link, $sweepstake, $user, $metadata) {
            $lockedSweepstake = Sweepstake::lockForUpdate()->find($sweepstake->id);

            if (! $lockedSweepstake->hasAvailableSlots($link->coupon_count)) {
                throw RedemptionException::sweepstakeLimitReached();
            }

            $startNumber = $lockedSweepstake->last_coupon_number + 1;
            $endNumber = $startNumber + $link->coupon_count - 1;

            $redemption = CouponRedemption::create([
                'sweepstake_id' => $lockedSweepstake->id,
                'redemption_link_id' => $link->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_phone' => $user->phone ?? null,
                'user_name' => $user->name ?? null,
                'coupon_count' => $link->coupon_count,
                'coupon_start_number' => $startNumber,
                'coupon_end_number' => $endNumber,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'redemption_channel' => $metadata['channel'] ?? 'web',
                'device_info' => $metadata['device_info'] ?? null,
            ]);

            $coupons = collect();
            for ($i = $startNumber; $i <= $endNumber; $i++) {
                $coupons->push([
                    'sweepstake_id' => $lockedSweepstake->id,
                    'redemption_id' => $redemption->id,
                    'user_id' => $user->id,
                    'coupon_number' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            SweepstakeCoupon::insert($coupons->toArray());

            $lockedSweepstake->update([
                'last_coupon_number' => $endNumber,
            ]);

            $link->incrementRedemptionCount();

            Log::info('Coupon redemption completed', [
                'redemption_id' => $redemption->id,
                'link_code' => $link->code,
                'user_id' => $user->id,
                'coupon_count' => $link->coupon_count,
                'numbers' => [$startNumber, $endNumber],
            ]);

            SendCouponNotificationJob::dispatch($redemption);

            return $redemption;
        });
    }

    /**
     * Obtiene o crea un usuario basado en email/teléfono
     */
    protected function getOrCreateUser(
        ?string $email,
        ?string $phone,
        ?string $name
    ): User {
        if (empty($email) && empty($phone)) {
            throw RedemptionException::missingContactInfo();
        }

        $user = $email
            ? User::where('email', $email)->first()
            : User::where('phone', $phone)->first();

        if ($user) {
            if (empty($user->phone) && $phone) {
                $user->update(['phone' => $phone]);
            }
            if (empty($user->email) && $email) {
                $user->update(['email' => $email]);
            }
            if (empty($user->name) && $name) {
                $user->update(['name' => $name]);
            }

            return $user;
        }

        return User::create([
            'email' => $email,
            'phone' => $phone,
            'name' => $name ?? ($phone ? 'Usuario '.substr($phone, -4) : 'Usuario'),
            'password' => bcrypt(str()->random(16)),
        ]);
    }

    /**
     * Reversa un cobro (anula cupones sin reutilizar números)
     */
    public function voidRedemption(
        int $redemptionId,
        string $reason,
        User $voidedBy
    ): void {
        DB::transaction(function () use ($redemptionId, $reason, $voidedBy) {
            $redemption = CouponRedemption::findOrFail($redemptionId);

            if ($redemption->is_voided) {
                throw RedemptionException::alreadyVoided();
            }

            $redemption->void($reason, $voidedBy);

            Log::warning('Coupon redemption voided', [
                'redemption_id' => $redemptionId,
                'reason' => $reason,
                'voided_by' => $voidedBy->id,
            ]);
        });
    }

    public function grantAutomaticReward(User $user, AutomaticReward $reward, Sweepstake $sweepstake): ?CouponRedemption
    {
        if (! $sweepstake->isAvailable()) {
            Log::warning('Cannot grant automatic reward: Sweepstake is not available', ['sweepstake_id' => $sweepstake->id, 'reward_id' => $reward->id]);

            return null;
        }

        if ($sweepstake->hasUserReachedLimit($user, $reward->coupon_amount)) {
            Log::warning('Cannot grant automatic reward: User reached sweepstake limit', ['sweepstake_id' => $sweepstake->id, 'user_id' => $user->id]);

            return null;
        }

        return DB::transaction(function () use ($reward, $sweepstake, $user) {
            $lockedSweepstake = Sweepstake::lockForUpdate()->find($sweepstake->id);

            if (! $lockedSweepstake->hasAvailableSlots($reward->coupon_amount)) {
                Log::warning('Cannot grant automatic reward: Sweepstake limit reached', ['sweepstake_id' => $sweepstake->id]);

                return null;
            }

            $startNumber = $lockedSweepstake->last_coupon_number + 1;
            $endNumber = $startNumber + $reward->coupon_amount - 1;

            $redemption = CouponRedemption::create([
                'sweepstake_id' => $lockedSweepstake->id,
                'automatic_reward_id' => $reward->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_phone' => $user->phone ?? null,
                'user_name' => $user->name ?? null,
                'coupon_count' => $reward->coupon_amount,
                'coupon_start_number' => $startNumber,
                'coupon_end_number' => $endNumber,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'redemption_channel' => 'automatic_reward',
                'device_info' => null,
            ]);

            $coupons = collect();
            for ($i = $startNumber; $i <= $endNumber; $i++) {
                $coupons->push([
                    'sweepstake_id' => $lockedSweepstake->id,
                    'redemption_id' => $redemption->id,
                    'user_id' => $user->id,
                    'coupon_number' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            SweepstakeCoupon::insert($coupons->toArray());

            $lockedSweepstake->update([
                'last_coupon_number' => $endNumber,
            ]);

            Log::info('Automatic reward coupons granted', [
                'redemption_id' => $redemption->id,
                'reward_id' => $reward->id,
                'user_id' => $user->id,
                'coupon_count' => $reward->coupon_amount,
                'numbers' => [$startNumber, $endNumber],
            ]);

            SendCouponNotificationJob::dispatch($redemption);

            return $redemption;
        });
    }
}
