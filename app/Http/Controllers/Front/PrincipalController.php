<?php

namespace App\Http\Controllers\Front;

use App\Enums\BannerScope;
use App\Enums\PromotionScheduleType;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PrincipalController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        $banners = $this->resolveActiveBanners($site);
        $promotions = $this->resolveActivePromotions($site);
        $games = $this->resolveGames($site);

        return Inertia::render('Principal', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'logo' => $site->logo ? asset('storage/'.$site->logo) : null,
                'content' => $site->content,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'links' => $site->links,
                'url' => $site->url,
            ],
            'banners' => $banners,
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
    private function resolveActiveBanners(Site $site): array
    {
        return Banner::query()
            ->where('is_active', true)
            ->where(function ($query) use ($site): void {
                $query
                    ->where('scope', BannerScope::Global->value)
                    ->orWhereHas('sites', fn ($siteQuery) => $siteQuery->where('sites.id', $site->id));
            })
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => (string) $banner->id,
                'title' => $banner->title,
                'section' => $banner->section,
                'image_url' => $this->resolveBannerImageUrl((string) $banner->image_path),
                'target_url' => $banner->target_url,
            ])
            ->values()
            ->all();
    }

    private function resolveBannerImageUrl(string $path): string
    {
        $publicStoragePath = public_path('storage/'.$path);

        if (is_file($publicStoragePath)) {
            return asset('storage/'.$path);
        }

        $fallbackName = basename($path);
        $fallbackPublicPath = public_path('images/banners/'.$fallbackName);

        if (is_file($fallbackPublicPath)) {
            return asset('images/banners/'.$fallbackName);
        }

        return asset('storage/'.$path);
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
            ->filter(fn (Promotion $promotion): bool => $promotion->isScheduledFor(now()))
            ->take(7)
            ->map(fn (Promotion $promotion): array => [
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
            ->map(fn (Game $game): array => [
                'id' => $game->id,
                'title' => $game->title,
                'description' => $game->description,
                'url' => $game->url,
                'is_featured' => $game->is_featured,
                'image_url' => $this->resolveGameImageUrl($game->image_path),
            ])
            ->values()
            ->all();
    }

    private function resolveGameImageUrl(?string $path): string
    {
        if ($path === null || $path === '') {
            return asset('images/games/game-placeholder.svg');
        }

        $publicStoragePath = public_path('storage/'.$path);

        if (is_file($publicStoragePath)) {
            return asset('storage/'.$path);
        }

        $fallbackName = basename($path);
        $fallbackPublicPath = public_path('images/games/'.$fallbackName);

        if (is_file($fallbackPublicPath)) {
            return asset('images/games/'.$fallbackName);
        }

        return asset('images/games/game-placeholder.svg');
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
            ->map(fn (int $day): string => $labels[$day] ?? (string) $day)
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
