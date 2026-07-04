<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RedemptionSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea los tipos de origen de canje por defecto:
     * - link: Link web compartido
     * - qr: Código QR escaneado
     * - manual: Canje manual por admin
     * - api: Integración vía API
     */
    public function run(): void
    {
        $sources = [
            [
                'code' => 'link',
                'name' => 'Link Web',
                'description' => 'Link compartido vía web, email o redes sociales',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'qr',
                'name' => 'Código QR',
                'description' => 'Código QR escaneado con cámara de móvil',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'manual',
                'name' => 'Canje Manual',
                'description' => 'Canje realizado manualmente por administrador',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'api',
                'name' => 'Integración API',
                'description' => 'Integración vía API externa',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('redemption_sources')->insert($sources);
    }
}
