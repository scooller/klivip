<?php

namespace App\Http\Controllers\Front;

use App\Enums\PromotionScheduleType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        return Inertia::render('Schedule', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
            ],
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'role' => UserRole::User->value,
                ] : null,
            ],
            'calendarDays' => $this->resolveCalendarDays($site),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveCalendarDays(Site $site): array
    {
        $startDate = CarbonImmutable::now()->startOfDay();
        $endDate = $startDate->addDays(29)->endOfDay();

        $promotions = Promotion::query()
            ->where('is_active', true)
            ->where(function ($query) use ($site): void {
                $query
                    ->where('site_id', $site->id)
                    ->orWhere('scope', 'global');
            })
            ->where(function ($query) use ($endDate): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $endDate);
            })
            ->where(function ($query) use ($startDate): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $startDate);
            })
            ->orderBy('starts_at')
            ->orderBy('title')
            ->get();

        $days = [];

        for ($offset = 0; $offset < 30; $offset++) {
            $day = $startDate->addDays($offset);
            $probe = $day->setTime(12, 0);

            $events = $promotions
                ->filter(fn (Promotion $promotion): bool => $promotion->isScheduledFor($probe))
                ->map(fn (Promotion $promotion): array => [
                    'title' => $promotion->title,
                    'offer_label' => $promotion->offer_label,
                    'description' => $promotion->description,
                    'schedule_label' => $this->promotionScheduleLabel($promotion),
                ])
                ->values()
                ->all();

            $days[] = [
                'date_iso' => $day->toDateString(),
                'events' => $events,
            ];
        }

        return $days;
    }

    private function promotionScheduleLabel(Promotion $promotion): string
    {
        return match ($promotion->schedule_type) {
            PromotionScheduleType::Recurrent => $this->recurringDaysLabel($promotion->recurrent_days),
            PromotionScheduleType::Special => $this->specialDateLabel($promotion->special_date?->toDateString()),
            default => 'Hoy',
        };
    }

    private function recurringDaysLabel(mixed $days): string
    {
        if (! is_array($days) || $days === []) {
            return 'Recurrente';
        }

        $labels = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
        ];

        $normalizedDays = array_values(array_unique(array_map('intval', $days)));
        sort($normalizedDays);

        return collect($normalizedDays)
            ->map(fn (int $day): string => $labels[$day] ?? (string) $day)
            ->implode(', ');
    }

    private function specialDateLabel(?string $date): string
    {
        if ($date === null) {
            return 'Especial';
        }

        return ucfirst(CarbonImmutable::parse($date)->translatedFormat('l'));
    }
}
