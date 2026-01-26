<?php

use App\Models\ChatParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth']]);

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId): bool {
    return ChatParticipant::where('chat_conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('chat.user.{userId}', function ($user, $userId): bool {
    return (int) $user->id === (int) $userId;
});
