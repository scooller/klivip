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
                'phone' => '+56970000001',
                'password' => 'P4ssw0rd',
                'role' => UserRole::SuperAdmin,
            ],
            [
                'email' => 'manager@klivip.test',
                'name' => 'Site Manager',
                'phone' => '+56970000002',
                'password' => 'C0ntr4señ4',
                'role' => UserRole::Manager,
            ],
        ];

        foreach ($panelUsers as $panelUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $panelUser['email']],
                [
                    'name' => $panelUser['name'],
                    'phone' => $panelUser['phone'],
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
                'phone' => '+56915482685',
            ],
            [
                'email' => 'cliente2@klivip.test',
                'name' => 'Cliente Dos',
                'phone' => '+56918901234',
            ],
        ];

        foreach ($customerUsers as $customerUser) {
            User::query()->updateOrCreate(
                ['email' => $customerUser['email']],
                [
                    'name' => $customerUser['name'],
                    'phone' => $customerUser['phone'],
                    'password' => 'password',
                    'role' => UserRole::User,
                ],
            );
        }
    }
}
