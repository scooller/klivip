<?php

namespace App\Models;

use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use Carbon\CarbonInterface;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'title',
        'offer_label',
        'description',
        'scope',
        'schedule_type',
        'recurrent_days',
        'special_date',
        'starts_at',
        'ends_at',
        'start_time',
        'end_time',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => PromotionScope::class,
            'schedule_type' => PromotionScheduleType::class,
            'recurrent_days' => 'array',
            'special_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isGlobal(): bool
    {
        return $this->scope === PromotionScope::Global;
    }

    public function isScheduledFor(CarbonInterface $dateTime): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $dateTime->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $dateTime->gt($this->ends_at)) {
            return false;
        }

        if (! $this->isWithinTimeRange($dateTime)) {
            return false;
        }

        return match ($this->schedule_type) {
            PromotionScheduleType::Standard => true,
            PromotionScheduleType::Recurrent => $this->matchesRecurringDay($dateTime),
            PromotionScheduleType::Special => $this->matchesSpecialDate($dateTime),
        };
    }

    private function matchesRecurringDay(CarbonInterface $dateTime): bool
    {
        $days = array_map('intval', $this->recurrent_days ?? []);

        if ($days === []) {
            return false;
        }

        return in_array($dateTime->dayOfWeekIso, $days, true);
    }

    private function matchesSpecialDate(CarbonInterface $dateTime): bool
    {
        if ($this->special_date === null) {
            return false;
        }

        return $this->special_date->isSameDay($dateTime);
    }

    private function isWithinTimeRange(CarbonInterface $dateTime): bool
    {
        if ($this->start_time === null && $this->end_time === null) {
            return true;
        }

        $currentTime = $dateTime->format('H:i:s');

        if ($this->start_time !== null && $currentTime < $this->start_time) {
            return false;
        }

        if ($this->end_time !== null && $currentTime > $this->end_time) {
            return false;
        }

        return true;
    }
}
