<?php

namespace App\Filament\Resources\SmsTemplates\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SmsTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Clave')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('Identificador único, ej: coupons-received')
                    ->disabled(fn ($record): bool => $record?->is_locked ?? false),

                TextInput::make('name.es')
                    ->label('Nombre (Español)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('sender_name')
                    ->label('Nombre del remitente')
                    ->maxLength(50)
                    ->default('Klivip')
                    ->helperText('Nombre que aparece como remitente'),

                Select::make('category')
                    ->label('Categoría')
                    ->required()
                    ->default('transactional')
                    ->options([
                        'transactional' => 'Transaccional',
                        'marketing' => 'Marketing',
                        'auth' => 'Autenticación',
                    ]),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),

                Toggle::make('is_locked')
                    ->label('Bloqueada (solo lectura)')
                    ->default(false)
                    ->helperText('Previene modificación del contenido una vez validado'),

                Textarea::make('body.es')
                    ->label('Cuerpo del mensaje (Español)')
                    ->required()
                    ->rows(4)
                    ->maxLength(480)
                    ->helperText('Usa {{ token }} para variables. Máx 160 caracteres por segmento SMS.')
                    ->live(onBlur: true)
                    ->columnSpanFull(),

                KeyValue::make('token_schema')
                    ->label('Tokens disponibles')
                    ->helperText('Define los tokens que se pueden usar en el cuerpo. Clave = nombre del token, Valor = tipo.')
                    ->keyLabel('Token')
                    ->valueLabel('Tipo')
                    ->columnSpanFull(),
            ]);
    }
}
