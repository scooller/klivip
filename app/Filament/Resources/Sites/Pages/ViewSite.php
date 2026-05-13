<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('visit')
                ->label('Visitar Sitio')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => $this->record->url)
                ->openUrlInNewTab()
                ->color('gray'),
            EditAction::make(),
        ];
    }
}
