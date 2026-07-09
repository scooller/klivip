<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use FinityLabs\FinMail\Helpers\TokenValue;
use FinityLabs\FinMail\Mail\TemplateMail;
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

    public function toMail(object $notifiable): TemplateMail
    {
        return TemplateMail::make('customer-profile-unlock-otp', app()->getLocale())
            ->models([
                'user' => $notifiable,
                'code' => new TokenValue($this->code),
                'site_name' => $this->siteName,
            ]);
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
