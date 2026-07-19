<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property-read Collection<int, SweepstakeCoupon> $winners
 */
class SweepstakeDraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'sweepstake_id',
        'drawn_by',
        'winners_count',
        'notes',
        'drawn_at',
        'notified',
    ];

    protected function casts(): array
    {
        return [
            'winners_count' => 'integer',
            'drawn_at' => 'datetime',
            'notified' => 'boolean',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function drawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }

    /**
     * Cupones ganadores ordenados por posición dentro del sorteo.
     *
     * @return BelongsToMany<SweepstakeCoupon>
     */
    public function winners(): BelongsToMany
    {
        return $this->belongsToMany(SweepstakeCoupon::class, 'sweepstake_draw_coupon')
            ->withPivot(['position', 'user_id'])
            ->orderByPivot('position', 'asc')
            ->withTimestamps();
    }
}
