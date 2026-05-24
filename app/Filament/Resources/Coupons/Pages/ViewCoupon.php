<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Actions\ShowCouponQrCodeAction;
use App\Filament\Resources\Coupons\CouponResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCoupon extends ViewRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ShowCouponQrCodeAction::make(),
            EditAction::make(),
        ];
    }
}
