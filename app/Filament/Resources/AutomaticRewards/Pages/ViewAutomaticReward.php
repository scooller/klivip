<?php

namespace App\Filament\Resources\AutomaticRewards\Pages;

use App\Filament\Resources\AutomaticRewards\AutomaticRewardResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomaticReward extends ViewRecord
{
    protected static string $resource = AutomaticRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
