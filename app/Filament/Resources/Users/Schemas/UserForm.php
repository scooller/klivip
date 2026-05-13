<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')
                    ->required(fn () => $authUser?->isSuperAdmin() ?? false)
                    ->options(UserRole::options())
                    ->default(UserRole::User->value)
                    ->dehydrated(fn () => $authUser?->isSuperAdmin() ?? false)
                    ->visible(fn () => $authUser?->isSuperAdmin() ?? false),
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
            ]);
    }
}
