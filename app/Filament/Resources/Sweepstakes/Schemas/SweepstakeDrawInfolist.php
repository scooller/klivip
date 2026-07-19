<?php

namespace App\Filament\Resources\Sweepstakes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SweepstakeDrawInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalle del sorteo')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('drawn_at')
                                ->label('Fecha')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('winners_count')
                                ->label('Ganadores')
                                ->badge()
                                ->color('success'),
                            TextEntry::make('drawnBy.name')
                                ->label('Realizado por')
                                ->placeholder('—'),
                            TextEntry::make('notified')
                                ->label('Notificado')
                                ->state(fn ($record): string => $record->notified ? 'Sí' : 'No')
                                ->badge()
                                ->color(fn ($record): string => $record->notified ? 'success' : 'gray'),
                            TextEntry::make('notes')
                                ->label('Observaciones')
                                ->columnSpanFull()
                                ->placeholder('—'),
                        ]),
                    ]),
                Section::make('Ganadores')
                    ->schema([
                        TextEntry::make('winners')
                            ->label(false)
                            ->state(fn ($record) => $record->winners)
                            ->view('filament.infolists.sweepstake-draw-winners'),
                    ]),
            ]);
    }
}
