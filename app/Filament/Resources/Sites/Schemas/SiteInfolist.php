<?php

namespace App\Filament\Resources\Sites\Schemas;

use App\Models\Site;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('-'),
                TextEntry::make('opening_hours')
                    ->label('Horario')
                    ->placeholder('-'),
                TextEntry::make('url')
                    ->label('URL')
                    ->state(fn (Site $record): string => $record->url)
                    ->url(fn (Site $record): string => $record->url)
                    ->openUrlInNewTab(),
                TextEntry::make('games.title')
                    ->label('Juegos')
                    ->badge()
                    ->separator(', '),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
