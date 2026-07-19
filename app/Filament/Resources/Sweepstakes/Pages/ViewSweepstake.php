<?php

namespace App\Filament\Resources\Sweepstakes\Pages;

use App\Filament\Exports\SweepstakeCouponsExport;
use App\Filament\Resources\Sweepstakes\Actions\DrawSweepstakeAction;
use App\Filament\Resources\Sweepstakes\SweepstakeResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewSweepstake extends ViewRecord
{
    protected static string $resource = SweepstakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DrawSweepstakeAction::make(),
            Action::make('export_csv')
                ->label('Exportar cupones a CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $export = SweepstakeCouponsExport::forSweepstake($this->record->id);
                    $fileName = $export->fileName();
                    $export->store($fileName, 'public');

                    return response()
                        ->download(Storage::disk('public')->path($fileName))
                        ->deleteFileAfterSend(true);
                }),
            EditAction::make(),
        ];
    }
}
