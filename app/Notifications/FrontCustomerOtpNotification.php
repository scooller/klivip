<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FrontCustomerOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $siteName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu codigo de acceso')
            ->greeting('Hola')
            ->line("Tu codigo de acceso para {$this->siteName} es:")
            ->line($this->code)
            ->line('Este codigo expira en 10 minutos.')
            ->line('Si no solicitaste este acceso, puedes ignorar este mensaje.');
    }
}
