<?php

namespace App\Filament\Resources\Sweepstakes\Pages;

use App\Filament\Resources\Sweepstakes\SweepstakeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSweepstakes extends ListRecords
{
    protected static string $resource = SweepstakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
