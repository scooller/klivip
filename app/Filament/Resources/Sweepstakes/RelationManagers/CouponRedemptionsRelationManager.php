<?php

namespace App\Filament\Resources\Sweepstakes\RelationManagers;

use App\Filament\Exports\CouponRedemptionsExport;
use App\Filament\Exports\HasCsvExportAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CouponRedemptionsRelationManager extends RelationManager
{
    use HasCsvExportAction;

    protected static string $relationship = 'couponRedemptions';

    protected static ?string $title = 'Redenciones';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder(fn ($record) => $record->user_name ?? '—'),
                TextColumn::make('user_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('redemptionLink.title')
                    ->label('Origen')
                    ->searchable()
                    ->placeholder('Manual'),
                TextColumn::make('coupon_count')
                    ->label('Cupones')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('coupon_start_number')
                    ->label('N° Asignados')
                    ->state(fn ($record): string => $record->coupon_start_number.' - '.$record->coupon_end_number),
                IconColumn::make('is_voided')
                    ->boolean()
                    ->label('Anulado'),
            ])
            ->filters([
                Filter::make('valid')
                    ->query(fn ($query) => $query->where('is_voided', false))
                    ->label('Solo válidos'),
                Filter::make('voided')
                    ->query(fn ($query) => $query->where('is_voided', true))
                    ->label('Solo anulados'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('void')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! $record->is_voided)
                    ->form([
                        Textarea::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record) {
                        $record->void($data['reason'], auth()->user());
                    }),
            ])
            ->headerActions([
                $this->makeCsvExportAction(
                    CouponRedemptionsExport::class,
                    fn () => CouponRedemptionsExport::forSweepstake($this->getOwnerRecord()->id),
                    'redenciones-sorteo'
                ),
            ]);
    }
}
