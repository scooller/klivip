<?php

namespace App\Filament\Exports;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;

/**
 * Provides a reusable CSV export Filament action with a column selector modal.
 *
 * Usage in a Filament page or relation manager:
 *
 *   use HasCsvExportAction;
 *
 *   protected function getHeaderActions(): array
 *   {
 *       return [
 *           $this->makeCsvExportAction(UsersExport::class, fn () => new UsersExport, 'usuarios'),
 *       ];
 *   }
 */
trait HasCsvExportAction
{
    /**
     * @param  class-string<CsvExporter>  $exporterClass
     * @param  callable(): CsvExporter  $exporterFactory
     */
    protected function makeCsvExportAction(string $exporterClass, callable $exporterFactory, string $baseFilename): Action
    {
        return Action::make('export_csv')
            ->label('Exportar a CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->form([
                CheckboxList::make('columns')
                    ->label('Columnas a exportar')
                    ->options($exporterClass::columns())
                    ->default(array_keys($exporterClass::columns()))
                    ->columns(2)
                    ->required()
                    ->bulkToggleable(),
            ])
            ->modalHeading('Exportar a CSV')
            ->modalDescription('Selecciona las columnas que deseas incluir en el archivo CSV.')
            ->modalSubmitActionLabel('Descargar CSV')
            ->action(function (array $data) use ($exporterFactory, $baseFilename): void {
                /** @var CsvExporter $exporter */
                $exporter = $exporterFactory();
                $columns = $data['columns'];

                $filename = sprintf(
                    '%s-%s.csv',
                    $baseFilename,
                    now()->format('Y-m-d-His')
                );

                $downloadUrl = $exporter->store($filename, $columns);

                Notification::make()
                    ->success()
                    ->title('CSV generado')
                    ->body('Se exportaron '.count($columns).' columnas correctamente.')
                    ->actions([
                        Action::make('download')
                            ->label('Descargar')
                            ->url($downloadUrl)
                            ->openUrlInNewTab(),
                    ])
                    ->persistent()
                    ->send();
            });
    }
}
