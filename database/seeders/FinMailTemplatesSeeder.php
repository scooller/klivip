<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinMailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $content = <<<HTML
        <h2>¡Hola {{ name }}!</h2>
        <p>Queríamos avisarte que has recibido <strong>{{ coupon_count }}</strong> cupones para el sorteo <strong>{{ sweepstake_name }}</strong>.</p>
        <p>¡Mucha suerte en el sorteo!</p>
        <p>El equipo de Klivip</p>
        HTML;

        $template = \FinityLabs\FinMail\Models\EmailTemplate::updateOrCreate(
            ['key' => 'coupons-received'],
            [
                'name' => ['es' => 'Cupones Recibidos'],
                'subject' => ['es' => '¡Has recibido {{ coupon_count }} cupones para {{ sweepstake_name }}!'],
                'preheader' => ['es' => 'Tus cupones han sido generados exitosamente.'],
                'body' => ['es' => $content],
                'from' => [],
                'reply_to' => [],
            ]
        );

        $template->versions()->updateOrCreate(
            ['version' => 1],
            [
                'subject' => ['es' => '¡Has recibido {{ coupon_count }} cupones para {{ sweepstake_name }}!'],
                'preheader' => ['es' => 'Tus cupones han sido generados exitosamente.'],
                'body' => ['es' => $content],
            ]
        );
    }
}
