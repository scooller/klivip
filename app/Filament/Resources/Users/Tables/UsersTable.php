<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof UserRole) {
                            return $state->label();
                        }

                        return str((string) $state)->headline()->toString();
                    })
                    ->sortable(),
                TextColumn::make('sites.name')
                    ->label('Sitios')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('validSweepstakeCoupons_count')
                    ->label('Cupones')
                    ->counts('validSweepstakeCoupons')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('last_login_at')
                    ->label('Último login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Nunca')
                    ->toggleable(),
                TextColumn::make('email_verified_at')
                    ->label('Email verificado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
