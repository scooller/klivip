<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;
        $currentRole = $record->role instanceof UserRole ? $record->role->value : (string) $record->role;
        $nextRole = (string) ($data['role'] ?? $currentRole);

        if ($currentRole === UserRole::User->value && $this->isPanelRole($nextRole) && ! filled($data['password'] ?? null)) {
            $this->addError('data.password', 'Debes definir una contraseña al promover un usuario al panel.');
            $this->halt();

            return $data;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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
