<?php

namespace App\Filament\Resources\Games;

use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Filament\Resources\Games\Pages\ViewGame;
use App\Filament\Resources\Games\Schemas\GameForm;
use App\Filament\Resources\Games\Schemas\GameInfolist;
use App\Filament\Resources\Games\Tables\GamesTable;
use App\Models\Game;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationLabel = 'Juegos';

    public static function getNavigationGroup(): ?string
    {
        return 'Juegos';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-puzzle-piece';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User || $user->isSuperAdmin() || $user->isAdmin()) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->whereHas('sites', function (Builder $builder) use ($user): void {
                $builder->whereIn('sites.id', $user->sites()->select('sites.id'));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return GameForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GamesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'create' => CreateGame::route('/create'),
            'view' => ViewGame::route('/{record}'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }
}
