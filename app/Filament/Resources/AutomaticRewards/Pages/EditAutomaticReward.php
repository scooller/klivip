<?php

namespace App\Filament\Resources\AutomaticRewards\Pages;

use App\Filament\Resources\AutomaticRewards\AutomaticRewardResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomaticReward extends EditRecord
{
    protected static string $resource = AutomaticRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
