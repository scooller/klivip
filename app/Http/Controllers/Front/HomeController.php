<?php

namespace App\Http\Controllers\Front;

use App\Enums\PromotionScheduleType;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        $promotions = $this->resolveActivePromotions($site);
        $games = $this->resolveGames($site);

        return Inertia::render('Home', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'content' => $site->content,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'url' => $site->url,
            ],
            'promotions' => $promotions,
            'games' => $games,
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ] : null,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function resolveActivePromotions(Site $site): array
    {
        return Promotion::query()
            ->where('is_active', true)
            ->where(function ($query) use ($site): void {
                $query
                    ->where('site_id', $site->id)
                    ->orWhere('scope', 'global');
            })
            ->latest('starts_at')
            ->limit(30)
            ->get()
            ->filter(fn(Promotion $promotion): bool => $promotion->isScheduledFor(now()))
            ->take(7)
            ->map(fn(Promotion $promotion): array => [
                'title' => $promotion->title,
                'offer_label' => $promotion->offer_label,
                'description' => $promotion->description,
                'schedule_label' => $this->promotionScheduleLabel($promotion),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveGames(Site $site): array
    {
        return $this->mapGames(
            $site->games()
                ->where('games.is_active', true)
                ->orderBy('game_site.sort_order')
                ->orderBy('games.sort_order')
                ->orderByDesc('games.is_featured')
                ->get(),
        );
    }

    /**
     * @param  Collection<int, Game>  $games
     * @return array<int, array<string, mixed>>
     */
    private function mapGames(Collection $games): array
    {
        return $games
            ->map(fn(Game $game): array => [
                'title' => $game->title,
                'description' => $game->description,
                'url' => $game->url,
                'is_featured' => $game->is_featured,
            ])
            ->values()
            ->all();
    }

    private function promotionScheduleLabel(Promotion $promotion): string
    {
        return match ($promotion->schedule_type) {
            PromotionScheduleType::Recurrent => $this->recurringDaysLabel($promotion->recurrent_days),
            PromotionScheduleType::Special => $this->specialDateLabel($promotion->special_date),
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
            ->map(fn(int $day): string => $labels[$day] ?? (string) $day)
            ->implode(', ');
    }

    private function specialDateLabel(?CarbonInterface $date): string
    {
        if ($date === null) {
            return 'Especial';
        }

        return ucfirst($date->translatedFormat('l'));
    }
}
