<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SystemHealthAlert extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $detail,
        private readonly array $context = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->title)
            ->line($this->detail);

        if (! empty($this->context)) {
            foreach ($this->context as $key => $value) {
                $message->line(ucfirst($key) . ': ' . $value);
            }
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->detail,
            'context' => $this->context,
        ];
    }
}
