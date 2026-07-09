<?php

namespace App\Filament\Resources\AutomaticRewards;

use App\Filament\Resources\AutomaticRewards\Pages\CreateAutomaticReward;
use App\Filament\Resources\AutomaticRewards\Pages\EditAutomaticReward;
use App\Filament\Resources\AutomaticRewards\Pages\ListAutomaticRewards;
use App\Filament\Resources\AutomaticRewards\Pages\ViewAutomaticReward;
use App\Filament\Resources\AutomaticRewards\Schemas\AutomaticRewardForm;
use App\Filament\Resources\AutomaticRewards\Schemas\AutomaticRewardInfolist;
use App\Filament\Resources\AutomaticRewards\Tables\AutomaticRewardsTable;
use App\Models\AutomaticReward;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AutomaticRewardResource extends Resource
{
    protected static ?string $model = AutomaticReward::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?string $navigationLabel = 'Recompensas Automáticas';

    protected static ?string $modelLabel = 'Recompensa Automática';

    protected static ?string $pluralModelLabel = 'Recompensas Automáticas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AutomaticRewardForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AutomaticRewardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutomaticRewardsTable::configure($table);
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
            'index' => ListAutomaticRewards::route('/'),
            'create' => CreateAutomaticReward::route('/create'),
            'view' => ViewAutomaticReward::route('/{record}'),
            'edit' => EditAutomaticReward::route('/{record}/edit'),
        ];
    }
}
