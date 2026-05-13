<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GamesRelationManager extends RelationManager
{
    protected static string $relationship = 'games';

    protected static ?string $title = 'Juegos del sitio';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->label('Orden en este sitio')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Juego')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('pivot.sort_order')
                    ->label('Orden en sitio'),
            ])
            ->defaultSort('game_site.sort_order')
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('sort_order')
                            ->label('Orden en este sitio')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
