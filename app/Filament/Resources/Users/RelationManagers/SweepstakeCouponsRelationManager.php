<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SweepstakeCouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'sweepstakeCoupons';

    protected static ?string $title = 'Cupones de sorteo';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('coupon_number')
                    ->label('Número')
                    ->formatStateUsing(fn($state): string => str_pad((string) $state, 4, '0', STR_PAD_LEFT))
                    ->sortable(),
                TextColumn::make('sweepstake.name')
                    ->label('Sorteo')
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
                    ->query(fn($query) => $query->where('is_voided', false))
                    ->label('Solo válidos'),
                Filter::make('voided')
                    ->query(fn($query) => $query->where('is_voided', true))
                    ->label('Solo anulados'),
                Filter::make('unused')
                    ->query(fn($query) => $query->where('is_used', false))
                    ->label('Sin usar'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
