<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FrontProfileUnlockLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $unlockUrl,
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
            ->subject('Link para desbloquear perfil')
            ->greeting('Hola')
            ->line("Solicitaste desbloquear la edicion de perfil en {$this->siteName}.")
            ->action('Desbloquear perfil', $this->unlockUrl)
            ->line('Este enlace expira en 15 minutos y solo puede usarse una vez.');
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
