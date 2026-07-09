<?php

namespace App\Filament\Resources\AutomaticRewards\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AutomaticRewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuración General')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre de la Recompensa')
                            ->required(),
                        Select::make('event_type')
                            ->label('Tipo de Evento')
                            ->options([
                                'registration' => 'Registro de Usuario',
                                'profile_update' => 'Actualización de Perfil',
                                'birthday' => 'Cumpleaños',
                                'anniversary' => 'Aniversario en la Plataforma',
                            ])
                            ->required(),
                        TextInput::make('coupon_amount')
                            ->label('Cantidad de Cupones')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Select::make('frequency')
                            ->label('Frecuencia')
                            ->options([
                                'once_per_user' => 'Una vez por usuario (Única vez)',
                                'once_per_sweepstake' => 'Una vez por Sorteo',
                                'yearly' => 'Una vez al año (Cumpleaños/Aniversario)',
                            ])
                            ->required()
                            ->default('once_per_user'),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
                Section::make('Filtros Opcionales')
                    ->description('Deja estos campos en blanco si la regla aplica para todos los sitios y todos los sorteos activos.')
                    ->schema([
                        Select::make('site_id')
                            ->label('Sitio Específico')
                            ->relationship('site', 'name')
                            ->searchable()
                            ->nullable(),
                        Select::make('sweepstake_id')
                            ->label('Sorteo Específico')
                            ->relationship('sweepstake', 'name')
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }
}
