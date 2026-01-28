<?php

use App\Models\ChatParticipant;
use App\Models\User;
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

Broadcast::channel('trips.branch.{branchId}', function ($user, $branchId): bool {
    return (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('trips.user.{userId}', function ($user, $userId): bool {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('trips.all', function ($user): bool {
    return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true);
});

Broadcast::channel('incidents.branch.{branchId}', function ($user, $branchId): bool {
    return (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('incidents.user.{userId}', function ($user, $userId): bool {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('incidents.all', function ($user): bool {
    return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true);
});

Broadcast::channel('dashboard.refresh', function ($user): bool {
    return (bool) $user;
});

Broadcast::channel('vehicles.all', function ($user): bool {
    return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true);
});

Broadcast::channel('drivers.all', function ($user): bool {
    return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true);
});

Broadcast::channel('maintenances.all', function ($user): bool {
    return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true);
});
