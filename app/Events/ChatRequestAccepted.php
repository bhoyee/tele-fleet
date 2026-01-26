<?php

namespace App\Events;

use App\Models\ChatConversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatRequestAccepted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ChatConversation $conversation, public int $userId)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.user.' . $this->conversation->created_by_user_id);
    }

    public function broadcastAs(): string
    {
        return 'chat.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'accepted_by' => $this->userId,
        ];
    }
}
