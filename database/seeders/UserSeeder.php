<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siteIds = Site::query()->pluck('id')->all();

        $panelUsers = [
            [
                'email' => 'admin@klivip.test',
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => UserRole::SuperAdmin,
            ],
            [
                'email' => 'manager@klivip.test',
                'name' => 'Site Manager',
                'password' => 'password',
                'role' => UserRole::Manager,
            ],
        ];

        foreach ($panelUsers as $panelUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $panelUser['email']],
                [
                    'name' => $panelUser['name'],
                    'password' => $panelUser['password'],
                    'role' => $panelUser['role'],
                ],
            );

            $user->sites()->syncWithoutDetaching($siteIds);
        }

        $customerUsers = [
            [
                'email' => 'cliente1@klivip.test',
                'name' => 'Cliente Uno',
            ],
            [
                'email' => 'cliente2@klivip.test',
                'name' => 'Cliente Dos',
            ],
        ];

        foreach ($customerUsers as $customerUser) {
            User::query()->updateOrCreate(
                ['email' => $customerUser['email']],
                [
                    'name' => $customerUser['name'],
                    'password' => 'password',
                    'role' => UserRole::User,
                ],
            );
        }
    }
}
