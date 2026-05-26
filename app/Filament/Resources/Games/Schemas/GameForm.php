<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('URL')
                    ->url()
                    ->maxLength(255)
                    ->nullable(),
                FileUpload::make('image_path')
                    ->label('Imagen')
                    ->image()
                    ->directory('games')
                    ->visibility('public')
                    ->helperText('Imagen del juego para el slider principal.')
                    ->nullable(),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
                Toggle::make('is_featured')
                    ->label('Destacado')
                    ->default(false)
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
                Select::make('sites')
                    ->label('Sitios disponibles')
                    ->relationship(
                        name: 'sites',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query) use ($user): void {
                            if ($user instanceof User && ! $user->isSuperAdmin()) {
                                $query->whereIn('id', $user->sites()->select('sites.id'));
                            }
                        },
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }
}
