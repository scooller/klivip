<?php

namespace App\Filament\Resources\SentSms\Schemas;

use App\Models\SentSms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;

class SentSmsInfolist
{
    /**
     * @return array<int, Component|\Filament\Infolists\Components\Component>
     */
    public static function schema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextEntry::make('to')
                        ->label('Destinatario'),

                    TextEntry::make('from')
                        ->label('Remitente')
                        ->placeholder('—'),

                    TextEntry::make('template.name.es')
                        ->label('Plantilla')
                        ->badge()
                        ->color('gray')
                        ->placeholder('Mensaje directo (sin plantilla)'),

                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge(),

                    TextEntry::make('subject')
                        ->label('Asunto')
                        ->placeholder('—'),

                    TextEntry::make('sent_at')
                        ->label('Enviado el')
                        ->dateTime()
                        ->placeholder('Pendiente'),

                    TextEntry::make('senderUser.name')
                        ->label('Enviado por')
                        ->placeholder('Sistema'),

                    TextEntry::make('sendable_type')
                        ->label('Relacionado con')
                        ->formatStateUsing(fn(?string $state): string => $state ? class_basename($state) : '—'),
                ]),

            TextEntry::make('body')
                ->label('Mensaje')
                ->columnSpanFull(),

            TextEntry::make('error_message')
                ->label('Error')
                ->color('danger')
                ->visible(fn(SentSms $record): bool => ! empty($record->error_message)),
        ];
    }
}
