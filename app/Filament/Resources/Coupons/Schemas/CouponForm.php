<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $schema
            ->components([
                Select::make('site_id')
                    ->label('Sitio')
                    ->required()
                    ->searchable()
                    ->options(function () use ($user): array {
                        $query = Site::query()->orderBy('name');

                        if ($user instanceof User && ! $user->isSuperAdmin()) {
                            $query->whereIn('id', $user->sites()->select('sites.id'));
                        }

                        return $query->pluck('name', 'id')->all();
                    }),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper(trim($state)))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn(Unique $rule, Get $get): Unique => $rule->where('site_id', $get('site_id')),
                    ),
                Select::make('type')
                    ->required()
                    ->options(CouponType::options())
                    ->default(CouponType::Message->value)
                    ->live(),
                TextInput::make('value')
                    ->required(fn(Get $get): bool => $get('type') !== CouponType::Message->value)
                    ->numeric()
                    ->minValue(0.01)
                    ->visible(fn(Get $get): bool => $get('type') !== CouponType::Message->value),
                TextInput::make('message')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn(Get $get): bool => $get('type') === CouponType::Message->value),
                TextInput::make('max_uses')
                    ->nullable()
                    ->integer()
                    ->default(1)
                    ->minValue(1),
                TextInput::make('used_count')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                DateTimePicker::make('valid_from')
                    ->required()
                    ->default(now()),
                DateTimePicker::make('valid_to')
                    ->nullable()
                    ->afterOrEqual('valid_from'),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Toggle::make('qr_enabled')
                    ->label('Habilitar cobro por QR')
                    ->default(false)
                    ->live(),
                Placeholder::make('qr_redeem_url')
                    ->label('URL de cobro QR')
                    ->content(function (?Coupon $record, Get $get): string {
                        if (! $get('qr_enabled')) {
                            return 'Activa el QR para generar un enlace de cobro.';
                        }

                        return $record?->qr_redeem_url ?? 'Guarda el cupon para generar el enlace QR.';
                    }),
            ]);
    }
}
