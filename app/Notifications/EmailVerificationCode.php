<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly ?string $recipientName = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->recipientName ?? $notifiable->name ?? null;

        return (new MailMessage)
            ->subject('Your Lost & Found verification code')
            ->when($name, fn (MailMessage $message) => $message->greeting('Hello '.$name.','))
            ->line('Use this verification code to finish creating your account:')
            ->line('Verification code: '.$this->code)
            ->line('This code expires in 15 minutes.')
            ->line('If you did not create an account, you can ignore this email.');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
