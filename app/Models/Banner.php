<?php

namespace App\Models;

use App\Enums\BannerScope;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'image_path',
        'target_url',
        'scope',
        'section',
        'sort_order',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => BannerScope::class,
            'section' => 'string',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)
            ->withTimestamps();
    }

    public function isGlobal(): bool
    {
        return $this->scope === BannerScope::Global;
    }
}
