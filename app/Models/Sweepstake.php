<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sweepstake extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
        'starts_at',
        'expires_at',
        'max_coupons',
        'max_coupons_per_user',
        'is_active',
        'is_published',
        'last_coupon_number',
        'prize_description',
        'draw_at',
        'draw_result',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'draw_at' => 'datetime',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'last_coupon_number' => 'integer',
            'max_coupons' => 'integer',
            'max_coupons_per_user' => 'integer',
        ];
    }

    // Relaciones
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function redemptionLinks(): HasMany
    {
        return $this->hasMany(RedemptionLink::class);
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function sweepstakeCoupons(): HasMany
    {
        return $this->hasMany(SweepstakeCoupon::class);
    }

    public function validCoupons(): HasMany
    {
        return $this->hasMany(SweepstakeCoupon::class)
            ->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where('is_published', true)
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>', $now);
    }

    // Métodos de negocio
    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->is_published
            && Carbon::now()->between($this->starts_at, $this->expires_at);
    }

    public function hasAvailableSlots(int $couponCount = 1): bool
    {
        if (! $this->max_coupons) {
            return true;
        }

        return ($this->last_coupon_number + $couponCount) <= $this->max_coupons;
    }

    public function getEmittedCouponsCount(): int
    {
        return $this->last_coupon_number;
    }

    public function getAvailableCouponsCount(): int
    {
        if (! $this->max_coupons) {
            return 2147483647; // INT_MAX
        }

        return max(0, $this->max_coupons - $this->last_coupon_number);
    }

    public function getValidCouponsCount(): int
    {
        return $this->sweepstakeCoupons()
            ->where('is_voided', false)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getUserCouponCount(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return $this->couponRedemptions()
            ->where('user_id', $user->id)
            ->where('is_voided', false)
            ->sum('coupon_count');
    }

    public function hasUserReachedLimit(?User $user, int $couponCount = 1): bool
    {
        if (! $this->max_coupons_per_user || ! $user) {
            return false;
        }

        return ($this->getUserCouponCount($user) + $couponCount) > $this->max_coupons_per_user;
    }
}
