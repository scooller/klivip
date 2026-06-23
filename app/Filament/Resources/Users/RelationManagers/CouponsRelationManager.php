<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'coupons';

    protected static ?string $title = 'Cupones del usuario';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('value')
                    ->label('Valor')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('site.name')
                    ->label('Sitio')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.redeem_code')
                    ->label('Código de cobro')
                    ->default('-'),
                TextColumn::make('pivot.redeemed_at')
                    ->label('Cobrado')
                    ->dateTime('d/m/Y H:i')
                    ->default('-'),
                TextColumn::make('pivot.created_at')
                    ->label('Asociado')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('valid_to')
                    ->label('Válido hasta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_active')
                    ->label('Activo')
                    ->badge()
                    ->color(fn(bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('coupons.created_at', 'desc')
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn($query) => $this->couponOptionsQuery($query)),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    private function couponOptionsQuery($query): mixed
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $query->orderBy('code');

        if (! $authUser->isSuperAdmin()) {
            $query->whereIn('site_id', $authUser->sites()->select('sites.id'));
        }

        return $query;
    }
}
