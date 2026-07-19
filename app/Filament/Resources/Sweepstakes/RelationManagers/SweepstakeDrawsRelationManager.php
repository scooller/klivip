<?php

namespace App\Filament\Resources\Sweepstakes\RelationManagers;

use App\Filament\Exports\SweepstakeDrawWinnersExport;
use App\Filament\Resources\Sweepstakes\Schemas\SweepstakeDrawInfolist;
use App\Services\SweepstakeDrawService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SweepstakeDrawsRelationManager extends RelationManager
{
    protected static string $relationship = 'draws';

    protected static ?string $title = 'Sorteos realizados';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('drawn_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('winners_count')
                    ->label('Ganadores')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('drawnBy.name')
                    ->label('Realizado por')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('notified')
                    ->boolean()
                    ->label('Notificado'),
                TextColumn::make('notes')
                    ->label('Observaciones')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('drawn_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->infolist(fn(Schema $schema): Schema => SweepstakeDrawInfolist::configure($schema))
                    ->modalWidth('2xl'),
                Action::make('notify')
                    ->label('Notificar')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->visible(fn($record): bool => ! $record->notified && $record->winners()->exists())
                    ->requiresConfirmation()
                    ->modalHeading('Enviar notificaciones a ganadores')
                    ->modalDescription('Se despachará un job en cola para enviar email y SMS a cada ganador con contacto registrado.')
                    ->action(function ($record): void {
                        $service = app(SweepstakeDrawService::class);
                        $ok = $service->dispatchNotifications($record, force: true);

                        Notification::make()
                            ->title($ok ? 'Notificaciones encoladas' : 'No se pudieron encolar')
                            ->body($ok ? 'Los ganadores serán notificados en breve.' : 'Revisa los logs para más detalle.')
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),
                Action::make('export_csv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn($record): bool => $record->winners()->exists())
                    ->action(function ($record) {
                        $export = SweepstakeDrawWinnersExport::forDraw($record->id);
                        $fileName = $export->fileName();
                        $export->store($fileName, 'public');

                        return response()
                            ->download(Storage::disk('public')->path($fileName))
                            ->deleteFileAfterSend(true);
                    }),
            ])
            ->headerActions([
                Action::make('help')
                    ->label('¿Cómo sortear?')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('gray')
                    ->modalContent(view('filament.actions.draw-sweepstake-help'))
                    ->modalSubmitActionLabel('Entendido')
                    ->modalCancelAction(false),
            ]);
    }
}
