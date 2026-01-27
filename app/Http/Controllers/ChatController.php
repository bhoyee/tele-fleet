<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Events\ChatRequestAccepted;
use App\Events\ChatRequestCreated;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Http\Requests\Chat\StoreDirectChatRequest;
use App\Http\Requests\Chat\StoreSupportChatRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Notifications\ChatRequestNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Carbon;
use Throwable;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = ChatConversation::with(['participants.user', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }])
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->orderByDesc('updated_at')
            ->get();

        $pendingRequests = $this->pendingRequestsFor($user);

        $users = collect();
        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $users = User::where('id', '!=', $user->id)->orderBy('name')->get();
        }

        return view('chat.index', compact('conversations', 'pendingRequests', 'users'));
    }

    public function show(ChatConversation $conversation, Request $request): View
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        $conversation->load([
            'participants.user',
            'messages' => function ($query): void {
                $query->orderBy('created_at')->with('user');
            },
        ]);

        $participant = $conversation->participants->firstWhere('user_id', $user->id);
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }

        return view('chat.show', compact('conversation'));
    }

    public function storeSupport(StoreSupportChatRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $issueType = $request->validated()['issue_type'];

        $targetRole = $issueType === ChatConversation::ISSUE_TECH
            ? User::ROLE_SUPER_ADMIN
            : User::ROLE_FLEET_MANAGER;

        $assignee = User::where('role', $targetRole)->orderBy('id')->first();

        if (! $assignee) {
            return redirect()
                ->route('chat.index')
                ->with('error', 'No staff available for this request.');
        }

        $conversation = ChatConversation::create([
            'type' => ChatConversation::TYPE_SUPPORT,
            'issue_type' => $issueType,
            'status' => ChatConversation::STATUS_PENDING,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        ChatParticipant::create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        ChatParticipant::create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $assignee->id,
        ]);

        try {
            Notification::send($assignee, new ChatRequestNotification($conversation));
        } catch (Throwable $exception) {
            Log::warning('Chat request notification failed.', [
                'conversation_id' => $conversation->id,
                'error' => $exception->getMessage(),
            ]);
        }
        try {
            event(new ChatRequestCreated($conversation, $assignee->id));
        } catch (BroadcastException $e) {
            Log::warning('Chat request broadcast failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
                'message' => 'Chat request sent.',
            ]);
        }

        return redirect()
            ->route('chat.show', $conversation)
            ->with('success', 'Chat request sent.');
    }

    public function storeDirect(StoreDirectChatRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $target = User::findOrFail($request->validated()['user_id']);

        if ($target->id === $user->id) {
            return redirect()->route('chat.index')->with('error', 'You cannot chat with yourself.');
        }

        $conversation = ChatConversation::create([
            'type' => ChatConversation::TYPE_DIRECT,
            'status' => ChatConversation::STATUS_PENDING,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $target->id,
        ]);

        ChatParticipant::create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        ChatParticipant::create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $target->id,
        ]);

        try {
            Notification::send($target, new ChatRequestNotification($conversation));
        } catch (Throwable $exception) {
            Log::warning('Chat request notification failed.', [
                'conversation_id' => $conversation->id,
                'error' => $exception->getMessage(),
            ]);
        }
        try {
            event(new ChatRequestCreated($conversation, $target->id));
        } catch (BroadcastException $e) {
            Log::warning('Chat request broadcast failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
                'message' => 'Chat request sent.',
            ]);
        }

        return redirect()
            ->route('chat.show', $conversation)
            ->with('success', 'Chat request sent.');
    }

    public function accept(ChatConversation $conversation, Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        $participant = $conversation->participants()->where('user_id', $user->id)->first();

        if ($participant && ! $participant->accepted_at) {
            $participant->update(['accepted_at' => now()]);
            $conversation->update(['status' => ChatConversation::STATUS_ACTIVE]);
            try {
                event(new ChatRequestAccepted($conversation, $user->id));
            } catch (BroadcastException $e) {
                Log::warning('Chat accept broadcast failed', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
            ]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function decline(ChatConversation $conversation, Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        $conversation->update(['status' => ChatConversation::STATUS_DECLINED]);

        if ($request->expectsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
            ]);
        }

        return redirect()->route('chat.index')->with('success', 'Chat request declined.');
    }

    public function close(ChatConversation $conversation, Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        if ($conversation->status !== ChatConversation::STATUS_CLOSED) {
            $conversation->update([
                'status' => ChatConversation::STATUS_CLOSED,
                'closed_by_user_id' => $user->id,
                'closed_at' => now(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
            ]);
        }

        return redirect()->route('chat.show', $conversation)->with('success', 'Chat closed.');
    }

    public function sendMessage(StoreChatMessageRequest $request, ChatConversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        if ($conversation->status !== ChatConversation::STATUS_ACTIVE) {
            return redirect()->route('chat.show', $conversation)->with('error', 'Chat is not active.');
        }

        $message = ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $request->validated()['message'],
        ]);

        $conversation->touch();
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $otherUsers = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get()
            ->pluck('user');

        try {
            Notification::send($otherUsers, new ChatMessageNotification($conversation, $message));
        } catch (Throwable $exception) {
            Log::warning('Chat message notification failed.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }
        try {
            event(new ChatMessageSent($conversation, $message));
        } catch (BroadcastException $e) {
            Log::warning('Chat message broadcast failed', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'user_name' => $user->name,
                    'message' => $message->message,
                    'created_at' => $message->created_at?->toDateTimeString(),
                ],
            ]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function widgetConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = ChatConversation::with(['participants.user', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }])
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->orderByDesc('updated_at')
            ->get();

        $conversationPayload = $conversations->map(function (ChatConversation $conversation) use ($user): array {
            $other = $conversation->participants->firstWhere('user_id', '!=', $user->id)?->user;
            $lastMessage = $conversation->messages->first();
            $participant = $conversation->participants->firstWhere('user_id', $user->id);
            $lastReadAt = $participant?->last_read_at;
            $unreadCount = $conversation->messages()
                ->where('user_id', '!=', $user->id)
                ->when($lastReadAt, function ($query) use ($lastReadAt): void {
                    $query->where('created_at', '>', $lastReadAt);
                })
                ->count();

            return [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'type' => $conversation->type,
                'issue_type' => $conversation->issue_type,
                'other_user' => $other?->name ?? 'Support',
                'last_message' => $lastMessage?->message,
                'last_message_at' => $lastMessage?->created_at?->toDateTimeString(),
                'unread_count' => $unreadCount,
                'closed_at' => $conversation->closed_at?->toDateTimeString(),
            ];
        });

        $pendingPayload = $this->pendingRequestsFor($user)->map(function (ChatConversation $conversation) use ($user): array {
            $other = $conversation->participants->firstWhere('user_id', '!=', $user->id)?->user;

            return [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'issue_type' => $conversation->issue_type,
                'other_user' => $other?->name ?? 'Support',
                'created_at' => $conversation->created_at?->toDateTimeString(),
            ];
        });

        $historyCutoff = Carbon::now()->subDays(30);
        $activeConversations = $conversationPayload
            ->reject(fn (array $item): bool => $item['status'] === ChatConversation::STATUS_CLOSED)
            ->values();
        $historyConversations = $conversationPayload
            ->filter(fn (array $item): bool => $item['status'] === ChatConversation::STATUS_CLOSED)
            ->filter(function (array $item) use ($historyCutoff): bool {
                if (empty($item['closed_at'])) {
                    return true;
                }
                return Carbon::parse($item['closed_at'])->greaterThanOrEqualTo($historyCutoff);
            })
            ->values();

        $totalUnread = $activeConversations->sum('unread_count');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
            ],
            'conversations' => $activeConversations,
            'history' => $historyConversations,
            'pending' => $pendingPayload,
            'unread_total' => $totalUnread,
        ]);
    }

    public function widgetConversation(ChatConversation $conversation, Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        $conversation->load(['participants.user', 'messages.user']);

        $participant = $conversation->participants->firstWhere('user_id', $user->id);
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }

        $other = $conversation->participants->firstWhere('user_id', '!=', $user->id)?->user;

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'type' => $conversation->type,
                'issue_type' => $conversation->issue_type,
                'other_user' => $other?->name ?? 'Support',
                'can_accept' => $conversation->status === ChatConversation::STATUS_PENDING
                    && $participant
                    && ! $participant->accepted_at,
                'can_reply' => $conversation->status === ChatConversation::STATUS_ACTIVE,
            ],
            'messages' => $conversation->messages->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'user_name' => $message->user?->name ?? 'User',
                'message' => $message->message,
                'created_at' => $message->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    private function authorizeParticipant(ChatConversation $conversation, User $user): void
    {
        $isParticipant = $conversation->participants()->where('user_id', $user->id)->exists();

        abort_unless($isParticipant, 403);
    }

    private function pendingRequestsFor(User $user)
    {
        return ChatConversation::with(['participants.user'])
            ->where('status', ChatConversation::STATUS_PENDING)
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('user_id', $user->id)->whereNull('accepted_at');
            })
            ->get();
    }
}
