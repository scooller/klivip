<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $role = (string) ($data['role'] ?? UserRole::User->value);

        if ($this->isPanelRole($role) && ! filled($data['password'] ?? null)) {
            $this->addError('data.password', 'Debes establecer una contraseña para crear usuarios con acceso al panel.');
            $this->halt();

            return $data;
        }

        if (! $this->isPanelRole($role) && ! filled($data['password'] ?? null)) {
            $data['password'] = Hash::make(Str::random(40));
        }

        return $data;
    }

    private function isPanelRole(string $role): bool
    {
        return in_array($role, [
            UserRole::SuperAdmin->value,
            UserRole::Admin->value,
            UserRole::Manager->value,
        ], true);
    }
}
