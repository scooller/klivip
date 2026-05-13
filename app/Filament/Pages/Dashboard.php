<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CouponStatsWidget;
use App\Filament\Widgets\PromotionStatsWidget;
use App\Filament\Widgets\UpcomingEventsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            PromotionStatsWidget::class,
            CouponStatsWidget::class,
            UpcomingEventsWidget::class,
        ];
    }
}
