<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'automatic_reward_id',
        'user_id',
        'sweepstake_id',
    ];

    public function reward()
    {
        return $this->belongsTo(AutomaticReward::class, 'automatic_reward_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sweepstake()
    {
        return $this->belongsTo(Sweepstake::class);
    }
}
