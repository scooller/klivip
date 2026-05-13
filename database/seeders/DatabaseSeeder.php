<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Site::query()->updateOrCreate(
            ['slug' => 'sitio1'],
            ['name' => 'Sitio 1', 'is_active' => true],
        );

        Site::query()->updateOrCreate(
            ['slug' => 'sitio2'],
            ['name' => 'Sitio 2', 'is_active' => true],
        );

        $this->call(UserSeeder::class);
        $this->call(TestDataSeeder::class);
    }
}
