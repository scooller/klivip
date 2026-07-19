<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SweepstakeCoupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sweepstake_id',
        'redemption_id',
        'user_id',
        'coupon_number',
        'is_voided',
        'voided_at',
        'voided_reason',
        'voided_by',
        'is_used',
        'used_at',
        'used_by',
    ];

    protected function casts(): array
    {
        return [
            'coupon_number' => 'integer',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function redemption(): BelongsTo
    {
        return $this->belongsTo(CouponRedemption::class, 'redemption_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * Sorteos en los que este cupón resultó ganador.
     *
     * @return BelongsToMany<SweepstakeDraw>
     */
    public function draws(): BelongsToMany
    {
        return $this->belongsToMany(SweepstakeDraw::class, 'sweepstake_draw_coupon')
            ->withPivot(['position', 'user_id'])
            ->withTimestamps();
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('is_voided', true);
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('is_used', false);
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('is_used', true);
    }

    public function isValid(): bool
    {
        return ! $this->is_voided && $this->deleted_at === null;
    }

    public function canParticipate(): bool
    {
        return $this->isValid() && ! $this->is_used;
    }

    public function markAsUsed(?User $usedBy = null): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'used_by' => $usedBy?->id,
        ]);
    }

    public function markAsVoided(string $reason, ?User $voidedBy = null): void
    {
        $this->update([
            'is_voided' => true,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'voided_by' => $voidedBy?->id,
        ]);
    }

    public function getDisplayNumber(): string
    {
        return sprintf('%s-%04d', $this->sweepstake->slug, $this->coupon_number);
    }
}
