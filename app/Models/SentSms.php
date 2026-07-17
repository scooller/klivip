<?php

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $sms_template_id
 * @property string $to
 * @property string|null $from
 * @property string|null $subject
 * @property string $body
 * @property SmsStatus $status
 * @property Carbon|null $sent_at
 * @property array<string, mixed>|null $metadata
 * @property string|null $error_message
 * @property int|null $sent_by
 * @property string|null $sendable_type
 * @property int|string|null $sendable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SmsTemplate|null $template
 * @property-read Model|null $sendable
 * @property-read User|null $senderUser
 */
class SentSms extends Model
{
    protected $fillable = [
        'sms_template_id',
        'to',
        'from',
        'subject',
        'body',
        'status',
        'sent_at',
        'metadata',
        'error_message',
        'sent_by',
        'sendable_type',
        'sendable_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SmsStatus::class,
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query
            ->where('sendable_type', $model->getMorphClass())
            ->where('sendable_id', $model->getKey());
    }

    public function scopeStatus(Builder $query, SmsStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => SmsStatus::Sent,
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(?string $error = null): void
    {
        $this->update([
            'status' => SmsStatus::Failed,
            'error_message' => $error,
            'metadata' => array_merge($this->metadata ?? [], [
                'failed_at' => now()->toIso8601String(),
                'error' => $error,
            ]),
        ]);
    }
}
