<?php

namespace App\Filament\Resources\Promotions;

use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Resources\Promotions\Schemas\PromotionForm;
use App\Filament\Resources\Promotions\Tables\PromotionsTable;
use App\Models\Promotion;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Promociones';

    protected static ?string $navigationLabel = 'Promociones';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('scope', 'global')
                ->orWhereIn('site_id', $user->sites()->select('sites.id'));
        });
    }

    public static function form(Schema $schema): Schema
    {
        return PromotionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromotionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/create'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }
}
