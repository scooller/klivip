<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'content',
        'address',
        'opening_hours',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getUrlAttribute(): string
    {
        $base = (string) config('app.url', 'http://klivip.test');
        $url = preg_replace('#^(https?://)#', "$1{$this->slug}.", $base);

        return $url ?? $base;
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function banners(): BelongsToMany
    {
        return $this->belongsToMany(Banner::class)
            ->withTimestamps();
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }
}
