<?php

namespace App\Filament\Widgets;

use App\Models\Coupon;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CouponStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $query = Coupon::query();

        if ($user && ! $user->isSuperAdmin()) {
            $query->whereIn('site_id', $user->sites()->select('sites.id'));
        }

        $totalCount = $query->clone()->count();

        $activeCount = $query->clone()
            ->where('is_active', true)
            ->count();

        $inactiveCount = $query->clone()
            ->where('is_active', false)
            ->count();

        return [
            Stat::make('Total de Cupones', $totalCount)
                ->description('Todos los cupones')
                ->icon('heroicon-o-tag')
                ->color('info'),

            Stat::make('Cupones Activos', $activeCount)
                ->description('Disponibles para usar')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Cupones Inactivos', $inactiveCount)
                ->description('Desactivados o expirados')
                ->icon('heroicon-o-minus-circle')
                ->color('danger'),
        ];
    }
}
