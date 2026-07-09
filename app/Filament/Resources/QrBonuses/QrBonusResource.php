<?php

namespace App\Filament\Resources\QrBonuses;

use App\Filament\Resources\QrBonuses\Pages\ManageQrBonuses;
use App\Models\QrBonus;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QrBonusResource extends Resource
{
    protected static ?string $model = QrBonus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Sorteos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nombre del Bono')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('coupon_count')
                    ->label('Cupones por QR')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                \Filament\Forms\Components\TextInput::make('max_redemptions')
                    ->label('Máximo de redenciones')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Dejar en blanco si no hay límite'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('coupon_count')
                    ->label('Cupones por QR')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('max_redemptions')
                    ->label('Max Redenciones')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQrBonuses::route('/'),
        ];
    }
}
