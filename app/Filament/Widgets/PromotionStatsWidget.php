<?php

namespace App\Filament\Widgets;

use App\Models\Promotion;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PromotionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $query = Promotion::query();

        if ($user && ! $user->isSuperAdmin()) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder->where('scope', 'global')
                    ->orWhereIn('site_id', $user->sites()->select('sites.id'));
            });
        }

        $activeCount = $query->clone()
            ->where('is_active', true)
            ->count();

        $inactiveCount = $query->clone()
            ->where('is_active', false)
            ->count();

        $expiringCount = $query->clone()
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now()->addDays(7))
            ->where('ends_at', '>', Carbon::now())
            ->count();

        return [
            Stat::make('Promociones Activas', $activeCount)
                ->description('Promociones en vigor')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Por Vencer (7 días)', $expiringCount)
                ->description('Vencimiento próximo')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),

            Stat::make('Inactivas', $inactiveCount)
                ->description('Promociones desactivadas')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
