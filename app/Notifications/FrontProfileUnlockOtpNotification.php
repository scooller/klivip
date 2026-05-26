<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FrontProfileUnlockOtpNotification extends Notification
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
            ->subject('Codigo para desbloquear perfil')
            ->greeting('Hola')
            ->line("Tu codigo para desbloquear la edicion de perfil en {$this->siteName} es:")
            ->line($this->code)
            ->line('Este codigo expira en 10 minutos y es de un solo uso.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
