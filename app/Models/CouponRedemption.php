<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CouponRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'sweepstake_id',
        'redemption_link_id',
        'automatic_reward_id',
        'user_id',
        'user_email',
        'user_phone',
        'user_name',
        'coupon_count',
        'coupon_start_number',
        'coupon_end_number',
        'ip_address',
        'user_agent',
        'redemption_channel',
        'device_info',
        'is_voided',
        'voided_at',
        'voided_reason',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'coupon_count' => 'integer',
            'coupon_start_number' => 'integer',
            'coupon_end_number' => 'integer',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
            'device_info' => 'array',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function redemptionLink(): BelongsTo
    {
        return $this->belongsTo(RedemptionLink::class);
    }

    public function automaticReward(): BelongsTo
    {
        return $this->belongsTo(AutomaticReward::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function sweepstakeCoupons(): HasMany
    {
        return $this->hasMany(SweepstakeCoupon::class, 'redemption_id');
    }

    public function validCoupons(): HasMany
    {
        return $this->hasMany(SweepstakeCoupon::class, 'redemption_id')
            ->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_voided', false);
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('is_voided', true);
    }

    public function void(string $reason, ?User $voidedBy = null): void
    {
        $this->update([
            'is_voided' => true,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'voided_by' => $voidedBy?->id,
        ]);

        $this->sweepstakeCoupons()->update([
            'is_voided' => true,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'voided_by' => $voidedBy?->id,
        ]);
    }

    public function getCouponNumbers(): array
    {
        return range($this->coupon_start_number, $this->coupon_end_number);
    }
}
