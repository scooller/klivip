<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\BannerScope;
use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BannerForm
{
    /**
     * @return array<int, string>
     */
    private static function userSiteIds(?User $user): array
    {
        if (! $user instanceof User || $user->isSuperAdmin()) {
            return Site::query()->pluck('id')->all();
        }

        return $user->sites()->pluck('sites.id')->all();
    }

    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Auth::user();
        $scopeOptions = $user instanceof User && ! $user->isSuperAdmin()
            ? [BannerScope::Sites->value => BannerScope::Sites->label()]
            : BannerScope::options();

        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titulo')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('image_path')
                    ->label('Imagen')
                    ->required()
                    ->disk('public')
                    ->directory('banners')
                    ->visibility('public')
                    ->image()
                    ->imageEditor(),
                TextInput::make('target_url')
                    ->label('URL destino')
                    ->url()
                    ->nullable()
                    ->maxLength(255),
                Select::make('scope')
                    ->label('Alcance')
                    ->required()
                    ->live()
                    ->default(BannerScope::Sites->value)
                    ->options($scopeOptions)
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === BannerScope::Global->value) {
                            $set('sites', []);
                        }
                    }),
                Select::make('sites')
                    ->label('Sitios')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->relationship(
                        name: 'sites',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query) use ($user): void {
                            if ($user instanceof User && ! $user->isSuperAdmin()) {
                                $query->whereIn('id', self::userSiteIds($user));
                            }
                        },
                    )
                    ->visible(fn (Get $get): bool => $get('scope') === BannerScope::Sites->value)
                    ->required(fn (Get $get): bool => $get('scope') === BannerScope::Sites->value),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Inicio de vigencia')
                    ->nullable(),
                DateTimePicker::make('ends_at')
                    ->label('Fin de vigencia')
                    ->nullable()
                    ->afterOrEqual('starts_at'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }
}
