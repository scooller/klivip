<?php

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property array<string, string> $name
 * @property string $category
 * @property array<string, string> $body
 * @property array<string, string>|null $token_schema
 * @property string|null $sender_name
 * @property bool $is_active
 * @property bool $is_locked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read HasMany<SentSms> $sentSms
 */
class SmsTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'category',
        'body',
        'token_schema',
        'sender_name',
        'is_active',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'body' => 'array',
            'token_schema' => 'array',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn (Builder $query) => $query->where('is_active', true));
    }

    /**
     * Resolve a localized body for the given locale.
     */
    public function bodyFor(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->body[$locale] ?? $this->body['es'] ?? $this->body[array_key_first($this->body)] ?? '';
    }

    /**
     * Resolve a localized name for the given locale.
     */
    public function nameFor(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->name[$locale] ?? $this->name['es'] ?? $this->name[array_key_first($this->name)] ?? $this->key;
    }

    /**
     * Render the SMS body with token substitution.
     *
     * @param  array<string, mixed>  $tokens
     */
    public function render(?string $locale = null, array $tokens = []): string
    {
        $body = $this->bodyFor($locale);

        foreach ($tokens as $key => $value) {
            $body = str_replace('{{ '.$key.' }}', (string) $value, $body);
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return $body;
    }

    public function sentSms(): HasMany
    {
        return $this->hasMany(SentSms::class);
    }

    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Track delivery stats for this template.
     *
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'total' => $this->sentSms()->count(),
            'sent' => $this->sentSms()->where('status', SmsStatus::Sent)->count(),
            'failed' => $this->sentSms()->where('status', SmsStatus::Failed)->count(),
            'queued' => $this->sentSms()->where('status', SmsStatus::Queued)->count(),
        ];
    }
}
