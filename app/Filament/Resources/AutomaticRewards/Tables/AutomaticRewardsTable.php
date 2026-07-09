<?php

namespace App\Filament\Resources\AutomaticRewards\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AutomaticRewardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->label('Evento')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'registration' => 'Registro',
                        'profile_update' => 'Actualizar Perfil',
                        'birthday' => 'Cumpleaños',
                        'anniversary' => 'Aniversario',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('coupon_amount')
                    ->label('Cupones')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('frequency')
                    ->label('Frecuencia')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'once_per_user' => '1 vez p/Usuario',
                        'once_per_sweepstake' => '1 vez p/Sorteo',
                        'yearly' => 'Anual',
                        default => $state,
                    })
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('site.name')
                    ->label('Sitio')
                    ->placeholder('Todos')
                    ->sortable(),
                TextColumn::make('sweepstake.name')
                    ->label('Sorteo')
                    ->placeholder('Todos (Activo)')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
