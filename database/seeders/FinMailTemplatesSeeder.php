<?php

namespace Database\Seeders;

use FinityLabs\FinMail\Models\EmailTemplate;
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

        $templates = [
            [
                'key' => 'customer-otp',
                'name' => ['es' => 'Código de acceso OTP'],
                'category' => 'auth',
                'subject' => ['es' => 'Tu código de acceso'],
                'preheader' => ['es' => 'Usa este código para iniciar sesión.'],
                'body' => ['es' => '<p>Hola,</p><p>Tu código de acceso para {{ site_name }} es:</p><h2>{{ code }}</h2><p>Este código expira en 10 minutos.</p><p>Si no solicitaste este acceso, puedes ignorar este mensaje.</p>'],
                'token_schema' => [
                    'code' => 'string',
                    'site_name' => 'string',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'customer-profile-unlock-otp',
                'name' => ['es' => 'Código de desbloqueo de perfil'],
                'category' => 'auth',
                'subject' => ['es' => 'Código para desbloquear perfil'],
                'preheader' => ['es' => 'Usa este código para desbloquear tu perfil.'],
                'body' => ['es' => '<p>Hola,</p><p>Tu código para desbloquear la edición de perfil en {{ site_name }} es:</p><h2>{{ code }}</h2><p>Este código expira en 10 minutos y es de un solo uso.</p>'],
                'token_schema' => [
                    'code' => 'string',
                    'site_name' => 'string',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'customer-profile-unlock-link',
                'name' => ['es' => 'Link de desbloqueo de perfil'],
                'category' => 'auth',
                'subject' => ['es' => 'Link para desbloquear perfil'],
                'preheader' => ['es' => 'Haz clic aquí para desbloquear tu perfil.'],
                'body' => ['es' => '<p>Hola,</p><p>Solicitaste desbloquear la edición de perfil en {{ site_name }}.</p><p><a href="{{ unlock_url }}">Desbloquear perfil</a></p><p>Este enlace expira en 15 minutos y solo puede usarse una vez.</p>'],
                'token_schema' => [
                    'unlock_url' => 'string',
                    'site_name' => 'string',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $data) {
            $template = EmailTemplate::firstOrNew(['key' => $data['key']]);
            $template->fill($data);
            $template->save();
        }
    }
}
