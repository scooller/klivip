<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'sweepstake_id',
        'name',
        'event_type',
        'coupon_amount',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'coupon_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function sweepstake()
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function claims()
    {
        return $this->hasMany(RewardClaim::class);
    }
}
