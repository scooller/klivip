<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class RedemptionLink extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (RedemptionLink $link) {
            // Delete associated coupons first
            foreach ($link->couponRedemptions as $redemption) {
                $redemption->sweepstakeCoupons()->delete();
                $redemption->delete();
            }
        });
    }

    protected $fillable = [
        'sweepstake_id',
        'redemption_source_id',
        'code',
        'title',
        'description',
        'coupon_count',
        'valid_from',
        'valid_until',
        'max_redemptions',
        'is_active',
        'redemption_count',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
            'redemption_count' => 'integer',
            'coupon_count' => 'integer',
            'max_redemptions' => 'integer',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function redemptionSource(): BelongsTo
    {
        return $this->belongsTo(RedemptionSource::class);
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function sweepstakeCoupons(): HasManyThrough
    {
        return $this->hasManyThrough(SweepstakeCoupon::class, CouponRedemption::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_redemptions')
                    ->orWhereColumn('redemption_count', '<', 'max_redemptions');
            });
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_redemptions && $this->redemption_count >= $this->max_redemptions) {
            return false;
        }

        return $this->sweepstake->isAvailable();
    }

    public function incrementRedemptionCount(): void
    {
        $this->increment('redemption_count');
    }
}
