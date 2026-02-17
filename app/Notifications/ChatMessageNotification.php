<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public function __construct(private readonly ChatConversation $conversation, private readonly ChatMessage $message)
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
            ->subject('New Chat Message')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You received a new chat message.')
            ->action('Open Chat', route('chat.show', $this->conversation));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
        ];
    }
}
