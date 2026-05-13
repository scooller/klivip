<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Enums\PromotionScheduleType;
use App\Enums\PromotionScope;
use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PromotionForm
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
            ? [PromotionScope::Site->value => PromotionScope::Site->label()]
            : PromotionScope::options();

        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                TextInput::make('offer_label')
                    ->label('Oferta')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Ejemplo: Jueves de pizza, Viernes 2x1, 15% OFF.'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->nullable(),
                Select::make('scope')
                    ->label('Alcance')
                    ->required()
                    ->live()
                    ->default(PromotionScope::Site->value)
                    ->options($scopeOptions)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $state === PromotionScope::Global->value ? $set('site_id', null) : null),
                Select::make('site_id')
                    ->label('Sitio')
                    ->searchable()
                    ->options(function () use ($user): array {
                        $query = Site::query()->orderBy('name');

                        if ($user instanceof User && ! $user->isSuperAdmin()) {
                            $query->whereIn('id', self::userSiteIds($user));
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->required(fn (Get $get): bool => $get('scope') === PromotionScope::Site->value)
                    ->visible(fn (Get $get): bool => $get('scope') === PromotionScope::Site->value),
                Select::make('schedule_type')
                    ->label('Tipo de calendario')
                    ->required()
                    ->live()
                    ->default(PromotionScheduleType::Standard->value)
                    ->options(PromotionScheduleType::options())
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state !== PromotionScheduleType::Recurrent->value) {
                            $set('recurrent_days', null);
                        }

                        if ($state !== PromotionScheduleType::Special->value) {
                            $set('special_date', null);
                        }
                    }),
                CheckboxList::make('recurrent_days')
                    ->label('Días recurrentes')
                    ->options([
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        7 => 'Domingo',
                    ])
                    ->columns(4)
                    ->visible(fn (Get $get): bool => $get('schedule_type') === PromotionScheduleType::Recurrent->value)
                    ->required(fn (Get $get): bool => $get('schedule_type') === PromotionScheduleType::Recurrent->value),
                DatePicker::make('special_date')
                    ->label('Fecha especial')
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('schedule_type') === PromotionScheduleType::Special->value)
                    ->required(fn (Get $get): bool => $get('schedule_type') === PromotionScheduleType::Special->value),
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
                    ->required()
                    ->default(true),
            ]);
    }
}
