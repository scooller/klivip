<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'coupon_count',
        'max_redemptions',
    ];
}
