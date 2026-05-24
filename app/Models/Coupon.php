<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_to',
        'is_active',
        'qr_enabled',
        'qr_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'is_active' => 'boolean',
            'qr_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon): void {
            if (! $coupon->qr_enabled) {
                $coupon->qr_token = null;

                return;
            }

            if (! is_string($coupon->qr_token) || $coupon->qr_token === '') {
                $coupon->qr_token = Str::lower(Str::random(48));
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from !== null && now()->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_to !== null && now()->gt($this->valid_to)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function getQrRedeemUrlAttribute(): ?string
    {
        if (! $this->qr_enabled || ! is_string($this->qr_token) || $this->qr_token === '' || ! $this->site) {
            return null;
        }

        return rtrim($this->site->url, '/').'/cupones/qr/'.$this->qr_token;
    }
}
