<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserWelcomeCredentials extends Notification
{
    use Queueable;

    public function __construct(private string $plainPassword)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $notifiable instanceof User ? $notifiable : null;
        $brandName = config('app.name', 'Tele-Fleet');

        return (new MailMessage)
            ->subject('Welcome to ' . $brandName)
            ->markdown('mail.user-welcome', [
                'user' => $user,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => route('login'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $brandName = config('app.name', 'Tele-Fleet');

        return [
            'type' => 'user_welcome',
            'message' => 'Your ' . $brandName . ' account has been created.',
        ];
    }
}
