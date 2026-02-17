<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ChatRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public function __construct(private readonly ChatConversation $conversation)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['database', 'mail']
            : ['database'];
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
