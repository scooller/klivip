<?php

namespace App\Filament\Exports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsersExport extends CsvExporter
{
    /**
     * @return array<string, string>
     */
    public static function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Nombre',
            'email' => 'Email',
            'phone' => 'Teléfono',
            'birth_date' => 'Fecha nacimiento',
            'role' => 'Rol',
            'sites' => 'Sitios',
            'email_verified_at' => 'Email verificado',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    protected function query(): Builder
    {
        return User::query()->with('sites');
    }

    /**
     * @param  array<string>  $selected
     * @return array<string, mixed>
     */
    protected function mapRow(object $record, array $selected): array
    {
        /** @var User $record */
        $row = [];

        if (in_array('sites', $selected)) {
            $row['sites'] = $record->sites->pluck('name')->implode(', ');
        }

        $row['id'] = $record->id;
        $row['name'] = $record->name;
        $row['email'] = $record->email;
        $row['phone'] = $record->phone;
        $row['birth_date'] = $record->birth_date;
        $row['role'] = $record->role instanceof UserRole ? $record->role->label() : (string) ($record->role ?? '');
        $row['email_verified_at'] = $record->email_verified_at;
        $row['created_at'] = $record->created_at;
        $row['updated_at'] = $record->updated_at;

        return $row;
    }
}
