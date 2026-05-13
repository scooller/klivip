<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponType;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label('Sitio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof CouponType) {
                            return $state->label();
                        }

                        return ucfirst((string) $state);
                    })
                    ->sortable(),
                TextColumn::make('value')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('used_count')
                    ->label('Usos')
                    ->sortable(),
                TextColumn::make('valid_to')
                    ->label('Válido hasta')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('site_id')
                    ->label('Sitio')
                    ->relationship(
                        'site',
                        'name',
                        modifyQueryUsing: function ($query) use ($user) {
                            if ($user instanceof User && ! $user->isSuperAdmin()) {
                                $query->whereIn('id', $user->sites()->select('sites.id'));
                            }
                        },
                    ),
                TernaryFilter::make('is_active'),
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
