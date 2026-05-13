<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case User = 'user';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::SuperAdmin->value => 'Super Admin',
            self::Admin->value => 'Admin',
            self::Manager->value => 'Manager',
            self::User->value => 'User',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}
