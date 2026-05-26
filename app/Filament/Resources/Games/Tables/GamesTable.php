<?php

namespace App\Filament\Resources\Games\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Imagen')
                    ->disk('public')
                    ->circular()
                    ->size(40),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sites_count')
                    ->label('Sitios')
                    ->counts('sites')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_featured')->label('Destacado'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => $user instanceof User && ($user->isSuperAdmin() || $user->isAdmin())),
                ]),
            ]);
    }
}
