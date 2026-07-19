<?php

namespace App\Filament\Resources\Sweepstakes;

use App\Filament\Resources\Sweepstakes\Schemas\SweepstakeForm;
use App\Models\Sweepstake;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SweepstakeResource extends Resource
{
    protected static ?string $model = Sweepstake::class;

    protected static ?string $modelLabel = 'Sorteo';

    protected static ?string $pluralModelLabel = 'Sorteos';

    protected static ?string $navigationLabel = 'Sorteos';

    protected static \UnitEnum|string|null $navigationGroup = 'Sorteos';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SweepstakeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SweepstakeTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('site');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RedemptionLinksRelationManager::class,
            RelationManagers\CouponRedemptionsRelationManager::class,
            RelationManagers\SweepstakeCouponsRelationManager::class,
            RelationManagers\SweepstakeDrawsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSweepstakes::route('/'),
            'create' => Pages\CreateSweepstake::route('/create'),
            'view' => Pages\ViewSweepstake::route('/{record}'),
            'edit' => Pages\EditSweepstake::route('/{record}/edit'),
        ];
    }
}
