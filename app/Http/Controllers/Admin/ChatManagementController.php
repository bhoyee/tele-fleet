<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatManagementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('type');

        $query = ChatConversation::withTrashed()->with(['creator', 'assignee', 'participants.user'])
            ->with(['messages' => function ($q): void {
                $q->latest()->limit(1);
            }])
            ->orderByDesc('updated_at');

        if ($status) {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $conversations = $query->get();

        return view('admin.chats.index', compact('conversations', 'status', 'type'));
    }

    public function show(ChatConversation $conversation): View
    {
        $conversation = ChatConversation::withTrashed()
            ->with(['creator', 'assignee', 'participants.user', 'messages.user'])
            ->findOrFail($conversation->id);

        return view('admin.chats.show', compact('conversation'));
    }

    public function close(ChatConversation $conversation, AuditLogService $auditLog): RedirectResponse
    {
        if ($conversation->status !== ChatConversation::STATUS_CLOSED) {
            $conversation->update([
                'status' => ChatConversation::STATUS_CLOSED,
                'closed_by_user_id' => request()->user()?->id,
                'closed_at' => now(),
            ]);
            $auditLog->log('chat.closed_by_admin', $conversation);
        }

        return redirect()
            ->route('admin.chats.show', $conversation)
            ->with('success', 'Conversation closed.');
    }

    public function destroy(ChatConversation $conversation, AuditLogService $auditLog): RedirectResponse
    {
        $auditLog->log('chat.deleted_by_admin', $conversation, [], [
            'messages' => ChatMessage::where('chat_conversation_id', $conversation->id)->count(),
        ]);
        $conversation->delete();

        return redirect()
            ->route('admin.chats.index')
            ->with('success', 'Conversation deleted permanently.');
    }
}
