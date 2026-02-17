<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketDeveloperReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DeveloperSupportReplyController extends Controller
{
    public function create(Request $request, SupportTicket $ticket): View
    {
        if ($ticket->category !== SupportTicket::CATEGORY_DEVELOPER) {
            abort(404);
        }

        $ticket->load(['messages.user', 'messages.attachments', 'user', 'branch']);

        return view('helpdesk.developer_reply', [
            'ticket' => $ticket,
            'isClosed' => $ticket->status === SupportTicket::STATUS_CLOSED,
        ]);
    }

    public function store(Request $request, SupportTicket $ticket): RedirectResponse
    {
        if ($ticket->category !== SupportTicket::CATEGORY_DEVELOPER) {
            abort(404);
        }

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return back()->with('error', 'This ticket is closed. Replies are disabled.');
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $externalName = trim((string) ($data['name'] ?? 'Developer'));
        $externalEmail = trim((string) ($data['email'] ?? ''));
        if ($externalName === '') {
            $externalName = 'Developer';
        }
        if ($externalEmail === '') {
            $externalEmail = null;
        }

        $messageHtml = nl2br(e(trim($data['message'])));
        $messageHtml = strip_tags($messageHtml, '<br>');
        $messageHtml = '<p>' . $messageHtml . '</p>';

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => null,
            'external_name' => $externalName,
            'external_email' => $externalEmail,
            'message' => $messageHtml,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('helpdesk', 'local');
            SupportTicketAttachment::create([
                'support_ticket_id' => $ticket->id,
                'support_ticket_message_id' => $message->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $ticket->loadMissing('user');
        $recipients = collect();
        if ($ticket->user) {
            $recipients->push($ticket->user);
        }
        $recipients = $recipients
            ->merge(User::query()->where('role', User::ROLE_SUPER_ADMIN)->get())
            ->unique('id')
            ->values();

        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, SupportTicketDeveloperReply::fromInbound($ticket, $message));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('success', 'Reply sent.');
    }
}

