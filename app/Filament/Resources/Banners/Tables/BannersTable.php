<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Enums\BannerScope;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Imagen')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scope')
                    ->label('Alcance')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof BannerScope) {
                            return $state->label();
                        }

                        return BannerScope::options()[(string) $state] ?? (string) $state;
                    })
                    ->sortable(),
                TextColumn::make('sites_count')
                    ->label('Sitios')
                    ->counts('sites')
                    ->formatStateUsing(fn($state, $record): string => $record->isGlobal() ? 'Global' : (string) $state),
                TextColumn::make('section')
                    ->label('Sección')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Desde')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Hasta')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('Alcance')
                    ->options(BannerScope::options()),
                SelectFilter::make('sites')
                    ->label('Sitio')
                    ->relationship(
                        'sites',
                        'name',
                        modifyQueryUsing: function ($query) use ($user): void {
                            if ($user instanceof User && ! $user->isSuperAdmin()) {
                                $query->whereIn('id', $user->sites()->select('sites.id'));
                            }
                        },
                    ),
                TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
