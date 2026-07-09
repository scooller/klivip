<?php

namespace App\Filament\Resources\AutomaticRewards\Pages;

use App\Filament\Resources\AutomaticRewards\AutomaticRewardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutomaticRewards extends ListRecords
{
    protected static string $resource = AutomaticRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
