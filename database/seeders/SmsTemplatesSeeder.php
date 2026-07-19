<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'coupons-received',
                'name' => ['es' => 'Cupones Recibidos'],
                'category' => 'transactional',
                'body' => ['es' => 'Klivip: Acabas de recibir {{ coupon_count }} cupones para el sorteo \'{{ sweepstake_name }}\'. Revisa tu cuenta.'],
                'token_schema' => [
                    'coupon_count' => 'int',
                    'sweepstake_name' => 'string',
                ],
                'sender_name' => 'Klivip',
                'is_active' => true,
            ],
            [
                'key' => 'customer-otp',
                'name' => ['es' => 'Código de acceso OTP'],
                'category' => 'auth',
                'body' => ['es' => 'Tu codigo de acceso para {{ site_name }} es: {{ code }}. Expira en 10 minutos.'],
                'token_schema' => [
                    'code' => 'string',
                    'site_name' => 'string',
                ],
                'sender_name' => 'Klivip',
                'is_active' => true,
            ],
            [
                'key' => 'customer-profile-unlock-otp',
                'name' => ['es' => 'Código de desbloqueo de perfil'],
                'category' => 'auth',
                'body' => ['es' => 'Tu codigo para desbloquear tu perfil en {{ site_name }} es: {{ code }}. Expira en 10 minutos.'],
                'token_schema' => [
                    'code' => 'string',
                    'site_name' => 'string',
                ],
                'sender_name' => 'Klivip',
                'is_active' => true,
            ],
            [
                'key' => 'sweepstake-reminder',
                'name' => ['es' => 'Recordatorio de Sorteo'],
                'category' => 'marketing',
                'body' => ['es' => 'Klivip: El sorteo \'{{ sweepstake_name }}\' ocurrira pronto. Tienes {{ coupon_count }} cupones participando. Mucha suerte!'],
                'token_schema' => [
                    'sweepstake_name' => 'string',
                    'coupon_count' => 'int',
                ],
                'sender_name' => 'Klivip',
                'is_active' => true,
            ],
            [
                'key' => 'prize-won',
                'name' => ['es' => 'Ganador de Sorteo'],
                'category' => 'transactional',
                'body' => ['es' => 'Klivip: ¡Felicidades {{ name }}! Tu cupon {{ coupon_number }} GANO en el sorteo \'{{ sweepstake_name }}\'. Premio: {{ prize }}. Pronto te contactaremos.'],
                'token_schema' => [
                    'name' => 'string',
                    'sweepstake_name' => 'string',
                    'prize' => 'string',
                    'coupon_number' => 'string',
                    'position' => 'int',
                ],
                'sender_name' => 'Klivip',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $data) {
            $template = SmsTemplate::firstOrNew(['key' => $data['key']]);
            $template->fill($data);
            $template->save();
        }
    }
}
