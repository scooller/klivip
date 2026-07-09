<?php

namespace App\Filament\Resources\QrBonuses\Pages;

use App\Filament\Resources\QrBonuses\QrBonusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQrBonuses extends ManageRecords
{
    protected static string $resource = QrBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
