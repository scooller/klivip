<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'links',
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
            'links' => 'array',
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

    public function banners(): BelongsToMany
    {
        return $this->belongsToMany(Banner::class)
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    // Nuevas relaciones del sistema de sorteos
    public function sweepstakes(): HasMany
    {
        return $this->hasMany(Sweepstake::class);
    }

    public function activeSweepstakes(): HasMany
    {
        return $this->hasMany(Sweepstake::class)
            ->where('is_active', true)
            ->where('is_published', true);
    }

    /**
     * Scope para filtrar sites activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
