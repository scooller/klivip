<?php

namespace App\Filament\Resources\Sweepstakes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SweepstakeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_id')
                    ->relationship('site', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Usado para identificar el sorteo en los números de cupón'),
                Textarea::make('description')
                    ->rows(3),
                Textarea::make('prize_description')
                    ->label('Descripción del premio')
                    ->rows(2),
                DateTimePicker::make('starts_at')
                    ->required()
                    ->native(false),
                DateTimePicker::make('expires_at')
                    ->required()
                    ->native(false),
                DateTimePicker::make('draw_at')
                    ->label('Fecha del sorteo')
                    ->native(false)
                    ->helperText('Fecha prevista para realizar el sorteo'),
                TextInput::make('max_coupons')
                    ->numeric()
                    ->minValue(1)
                    ->label('Máximo de cupones total')
                    ->helperText('Dejar vacío para sin límite'),
                TextInput::make('max_coupons_per_user')
                    ->numeric()
                    ->minValue(1)
                    ->label('Máximo por usuario')
                    ->helperText('Dejar vacío para sin límite'),
                Toggle::make('is_active')
                    ->default(true)
                    ->label('Activo'),
                Toggle::make('is_published')
                    ->default(false)
                    ->label('Publicado (visible públicamente)'),
                Textarea::make('draw_result')
                    ->rows(5)
                    ->label('Resultado')
                    ->helperText('Ganadores, observaciones, etc.')
                    ->visible(fn ($context) => $context === 'edit'),
            ]);
    }
}
