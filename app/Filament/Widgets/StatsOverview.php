<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Cupones', \App\Models\SweepstakeCoupon::count())
                ->icon('heroicon-o-ticket'),
            Stat::make('Usuarios', \App\Models\User::count())
                ->icon('heroicon-o-users'),
            Stat::make('Sitios', \App\Models\Site::count())
                ->icon('heroicon-o-globe-alt'),
            Stat::make('Sorteos', \App\Models\Sweepstake::count())
                ->icon('heroicon-o-gift'),
        ];
    }
}
