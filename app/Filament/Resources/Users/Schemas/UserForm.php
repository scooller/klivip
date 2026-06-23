<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('phone')
                    ->label('Telefono')
                    ->tel()
                    ->maxLength(25)
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->live()
                    ->required(fn () => $authUser?->isSuperAdmin() ?? false)
                    ->options(UserRole::options())
                    ->default(UserRole::User->value)
                    ->dehydrated(fn () => $authUser?->isSuperAdmin() ?? false)
                    ->visible(fn () => $authUser?->isSuperAdmin() ?? false),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Obligatoria para roles de panel (Super Admin, Admin y Manager).')
                    ->required(fn (Get $get, string $operation): bool => $operation === 'create' && $get('role') !== UserRole::User->value)
                    ->visible(fn (Get $get): bool => $get('role') !== UserRole::User->value),
                Select::make('sites')
                    ->label('Sitios')
                    ->multiple()
                    ->relationship('sites', 'name')
                    ->preload()
                    ->searchable()
                    ->options(function () use ($authUser): array {
                        $query = Site::query()->orderBy('name');

                        if ($authUser instanceof User && ! $authUser->isSuperAdmin()) {
                            $query->whereIn('id', $authUser->sites()->select('sites.id'));
                        }

                        return $query->pluck('name', 'id')->all();
                    }),
                Select::make('coupons')
                    ->label('Cupones')
                    ->multiple()
                    ->relationship('coupons', 'code')
                    ->preload()
                    ->searchable()
                    ->options(function () use ($authUser): array {
                        $query = Coupon::query()->orderBy('code');

                        if ($authUser instanceof User && ! $authUser->isSuperAdmin()) {
                            $query->whereIn('site_id', $authUser->sites()->select('sites.id'));
                        }

                        return $query->pluck('code', 'id')->all();
                    }),
            ]);
    }
}
