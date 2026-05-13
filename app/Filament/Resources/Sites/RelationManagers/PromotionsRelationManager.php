<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromotionsRelationManager extends RelationManager
{
    protected static string $relationship = 'promotions';

    protected static ?string $title = 'Programacion diaria del sitio';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titulo')
                    ->required()
                    ->maxLength(255),
                TextInput::make('offer_label')
                    ->label('Oferta')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->rows(3)
                    ->nullable(),
                TextInput::make('scope')
                    ->default(PromotionScope::Site->value)
                    ->dehydrated()
                    ->hidden(),
                TextInput::make('schedule_type')
                    ->label('Tipo de calendario')
                    ->default(PromotionScheduleType::Standard->value)
                    ->dehydrated()
                    ->hidden(),
                CheckboxList::make('recurrent_days')
                    ->label('Dias recurrentes')
                    ->options([
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miercoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sabado',
                        7 => 'Domingo',
                    ])
                    ->columns(4)
                    ->helperText('Si seleccionas dias, la promocion sera recurrente.'),
                DatePicker::make('special_date')
                    ->label('Fecha especial')
                    ->native(false)
                    ->nullable(),
                DateTimePicker::make('starts_at')
                    ->label('Inicio de vigencia')
                    ->nullable(),
                DateTimePicker::make('ends_at')
                    ->label('Fin de vigencia')
                    ->nullable()
                    ->afterOrEqual('starts_at'),
                TimePicker::make('start_time')
                    ->label('Hora inicio')
                    ->seconds(false)
                    ->nullable(),
                TimePicker::make('end_time')
                    ->label('Hora fin')
                    ->seconds(false)
                    ->nullable(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('offer_label')
                    ->label('Oferta')
                    ->searchable(),
                TextColumn::make('schedule_type')
                    ->label('Calendario')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof PromotionScheduleType) {
                            return $state->label();
                        }

                        return PromotionScheduleType::options()[(string) $state] ?? (string) $state;
                    }),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label('Desde')
                    ->dateTime(),
                TextColumn::make('ends_at')
                    ->label('Hasta')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! empty($data['special_date'])) {
                            $data['schedule_type'] = PromotionScheduleType::Special->value;
                            $data['recurrent_days'] = null;

                            return $data;
                        }

                        if (! empty($data['recurrent_days'])) {
                            $data['schedule_type'] = PromotionScheduleType::Recurrent->value;
                            $data['special_date'] = null;

                            return $data;
                        }

                        $data['schedule_type'] = PromotionScheduleType::Standard->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! empty($data['special_date'])) {
                            $data['schedule_type'] = PromotionScheduleType::Special->value;
                            $data['recurrent_days'] = null;

                            return $data;
                        }

                        if (! empty($data['recurrent_days'])) {
                            $data['schedule_type'] = PromotionScheduleType::Recurrent->value;
                            $data['special_date'] = null;

                            return $data;
                        }

                        $data['schedule_type'] = PromotionScheduleType::Standard->value;

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
