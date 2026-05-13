<?php

namespace App\Filament\Resources\Sites;

use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\Pages\ListSites;
use App\Filament\Resources\Sites\Pages\ViewSite;
use App\Filament\Resources\Sites\RelationManagers\GamesRelationManager;
use App\Filament\Resources\Sites\RelationManagers\PromotionsRelationManager;
use App\Filament\Resources\Sites\Schemas\SiteForm;
use App\Filament\Resources\Sites\Schemas\SiteInfolist;
use App\Filament\Resources\Sites\Tables\SitesTable;
use App\Models\Site;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Sitios';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function form(Schema $schema): Schema
    {
        return SiteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SiteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $authUser = Auth::user();

        if (! $authUser instanceof User || $authUser->isSuperAdmin()) {
            return $query;
        }

        return $query->whereIn('id', $authUser->sites()->select('sites.id'));
    }

    public static function getRelations(): array
    {
        return [
            PromotionsRelationManager::class,
            GamesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSites::route('/'),
            'create' => CreateSite::route('/create'),
            'view' => ViewSite::route('/{record}'),
            'edit' => EditSite::route('/{record}/edit'),
        ];
    }
}
