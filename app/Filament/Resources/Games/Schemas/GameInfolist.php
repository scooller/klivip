<?php

namespace App\Filament\Resources\Games\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GameInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('url')
                    ->placeholder('-')
                    ->url(fn ($state): ?string => is_string($state) ? $state : null)
                    ->openUrlInNewTab(),
                TextEntry::make('sites.name')
                    ->label('Sitios')
                    ->badge()
                    ->separator(', '),
                TextEntry::make('sort_order')
                    ->label('Orden'),
                TextEntry::make('description')
                    ->label('Descripción')
                    ->placeholder('-'),
                IconEntry::make('is_featured')
                    ->label('Destacado')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
