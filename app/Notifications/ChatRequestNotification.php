<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ChatRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ChatConversation $conversation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New Chat Request')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have a new chat request.')
            ->action('Open Chat', route('chat.show', $this->conversation))
            ->line('Please accept or decline the request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'status' => $this->conversation->status,
            'type' => $this->conversation->type,
            'issue_type' => $this->conversation->issue_type,
        ];
    }
}
