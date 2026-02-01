<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketCreated;
use App\Notifications\SupportTicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureHelpDeskEnabled();

        $user = $request->user();
        $query = SupportTicket::with(['user', 'branch'])
            ->latest();

        if (! $this->isManager($user)) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->get();

        return view('helpdesk.index', compact('tickets', 'user'));
    }

    public function create(Request $request): View
    {
        $this->ensureHelpDeskEnabled();

        return view('helpdesk.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureHelpDeskEnabled();

        $data = $request->validate([
            'category' => ['required', 'in:' . implode(',', [
                SupportTicket::CATEGORY_ADMIN,
                SupportTicket::CATEGORY_TECH,
            ])],
            'priority' => ['required', 'in:' . implode(',', [
                SupportTicket::PRIORITY_LOW,
                SupportTicket::PRIORITY_MEDIUM,
                SupportTicket::PRIORITY_HIGH,
                SupportTicket::PRIORITY_CRITICAL,
            ])],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $user = $request->user();
        $description = $this->sanitizeDescription($data['description']);

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => SupportTicket::STATUS_OPEN,
            'subject' => $data['subject'],
            'description' => $description,
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $description,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('helpdesk', 'local');
            SupportTicketAttachment::create([
                'support_ticket_id' => $ticket->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $recipients = User::query()
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER])
            ->get();
        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, new SupportTicketCreated($ticket));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('helpdesk.show', $ticket)
            ->with('success', 'Support ticket submitted.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->ensureHelpDeskEnabled();
        $this->authorizeTicket($ticket, $request->user());

        $ticket->load(['user', 'branch', 'attachments', 'messages.user']);

        return view('helpdesk.show', compact('ticket'));
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->ensureHelpDeskEnabled();

        $user = $request->user();
        if (! $this->isManager($user)) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', [
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_IN_PROGRESS,
                SupportTicket::STATUS_RESOLVED,
                SupportTicket::STATUS_CLOSED,
            ])],
            'priority' => ['required', 'in:' . implode(',', [
                SupportTicket::PRIORITY_LOW,
                SupportTicket::PRIORITY_MEDIUM,
                SupportTicket::PRIORITY_HIGH,
                SupportTicket::PRIORITY_CRITICAL,
            ])],
        ]);

        $ticket->update($data);

        return redirect()
            ->route('helpdesk.show', $ticket)
            ->with('success', 'Ticket updated.');
    }

    public function storeMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->ensureHelpDeskEnabled();
        $this->authorizeTicket($ticket, $request->user());

        $data = $request->validate([
            'message' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $this->sanitizeDescription($data['message']),
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

        $recipients = $this->replyRecipients($ticket, $request->user());
        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, new SupportTicketReply($ticket, $message));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('helpdesk.show', $ticket)
            ->with('success', 'Reply sent.');
    }

    public function downloadAttachment(Request $request, SupportTicket $ticket, SupportTicketAttachment $attachment): Response
    {
        $this->ensureHelpDeskEnabled();
        $this->authorizeTicket($ticket, $request->user());

        if ($attachment->support_ticket_id !== $ticket->id) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->ensureHelpDeskEnabled();

        $user = $request->user();
        if (! $user || $user->role !== User::ROLE_SUPER_ADMIN) {
            abort(403);
        }

        $ticket->load('attachments', 'messages');

        foreach ($ticket->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->path);
            $attachment->delete();
        }

        $ticket->messages()->delete();
        $ticket->delete();

        return redirect()
            ->route('helpdesk.index')
            ->with('success', 'Ticket deleted permanently.');
    }

    private function isManager(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true);
    }

    private function authorizeTicket(SupportTicket $ticket, ?User $user): void
    {
        if (! $user) {
            abort(403);
        }

        if ($this->isManager($user)) {
            return;
        }

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }
    }

    private function ensureHelpDeskEnabled(): void
    {
        if (config('app.realtime_enabled')) {
            abort(403, 'Help Desk is disabled while realtime chat is enabled.');
        }
    }

    private function sanitizeDescription(string $description): string
    {
        return strip_tags($description, '<p><br><b><strong><i><em><ul><ol><li><a>');
    }

    private function replyRecipients(SupportTicket $ticket, User $sender)
    {
        if ($this->isManager($sender)) {
            return User::query()
                ->where('id', $ticket->user_id)
                ->get();
        }

        return User::query()
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER])
            ->get();
    }
}
