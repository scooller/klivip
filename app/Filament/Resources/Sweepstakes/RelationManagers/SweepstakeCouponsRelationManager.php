<?php

namespace App\Filament\Resources\Sweepstakes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SweepstakeCouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'sweepstakeCoupons';

    protected static ?string $title = 'Cupones';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('coupon_number')
                    ->label('Número')
                    ->sortable(),
                TextColumn::make('getDisplayNumber')
                    ->label('Display')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('redemption.created_at')
                    ->label('Fecha de cobro')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_voided')
                    ->boolean()
                    ->label('Anulado'),
                IconColumn::make('is_used')
                    ->boolean()
                    ->label('Usado en sorteo'),
            ])
            ->filters([
                Filter::make('valid')
                    ->query(fn ($query) => $query->where('is_voided', false)->whereNull('deleted_at'))
                    ->label('Solo válidos'),
                Filter::make('voided')
                    ->query(fn ($query) => $query->where('is_voided', true))
                    ->label('Solo anulados'),
                Filter::make('unused')
                    ->query(fn ($query) => $query->where('is_used', false))
                    ->label('Sin usar'),
            ])
            ->defaultSort('coupon_number');
    }
}
