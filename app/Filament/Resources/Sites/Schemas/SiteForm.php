<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash']),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->directory('sites/logos')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->nullable(),
                Repeater::make('links')
                    ->label('Links')
                    ->schema([
                        TextInput::make('label')
                            ->label('Texto')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('url')
                            ->label('URL')
                            ->required()
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->nullable(),
                TextInput::make('address')
                    ->label('Dirección')
                    ->maxLength(255)
                    ->nullable(),
                TextInput::make('opening_hours')
                    ->label('Horario')
                    ->maxLength(255)
                    ->placeholder('Lun-Dom 10:00 - 22:00')
                    ->nullable(),
                RichEditor::make('content')
                    ->label('Contenido')
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                        'link',
                        'blockquote',
                        'undo',
                        'redo',
                    ])
                    ->nullable(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
