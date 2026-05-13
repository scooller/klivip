<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSite extends EditRecord
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
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
