<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Models\AppSetting;
use App\Notifications\DeveloperSupportTicketMessage;
use App\Notifications\DeveloperSupportTicketCreated;
use App\Notifications\SupportTicketCreated;
use App\Notifications\SupportTicketReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

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

        $user = $request->user();
        $developerMode = $user && $user->role === User::ROLE_SUPER_ADMIN && $request->boolean('developer');

        return view('helpdesk.create', [
            'developerMode' => $developerMode,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureHelpDeskEnabled();

        $user = $request->user();
        $allowedCategories = [
            SupportTicket::CATEGORY_ADMIN,
            SupportTicket::CATEGORY_TECH,
        ];
        if ($user && $user->role === User::ROLE_SUPER_ADMIN) {
            $allowedCategories[] = SupportTicket::CATEGORY_DEVELOPER;
        }

        $data = $request->validate([
            'category' => ['required', Rule::in($allowedCategories)],
            'priority' => ['required', 'in:' . implode(',', [
                SupportTicket::PRIORITY_LOW,
                SupportTicket::PRIORITY_MEDIUM,
                SupportTicket::PRIORITY_HIGH,
                SupportTicket::PRIORITY_CRITICAL,
            ])],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'include_diagnostics' => ['nullable'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $description = $this->sanitizeDescription($data['description']);
        $descriptionPlain = trim(preg_replace('/\s+/', ' ', strip_tags($description)));

        $isDeveloperTicket = $data['category'] === SupportTicket::CATEGORY_DEVELOPER;
        if ($isDeveloperTicket && (! $user || $user->role !== User::ROLE_SUPER_ADMIN)) {
            abort(403);
        }

        $diagnosticsText = '';
        if ($isDeveloperTicket && $request->boolean('include_diagnostics', true)) {
            $diagnosticsLines = [];
            $diagnosticsLines[] = 'Time: ' . now()->toDateTimeString();
            $diagnosticsLines[] = 'URL: ' . (string) $request->fullUrl();
            $diagnosticsLines[] = 'User: ' . ($user->name ?? 'N/A') . ' (' . ($user->email ?? 'N/A') . ')';
            $diagnosticsLines[] = 'Role: ' . ($user->role ?? 'N/A');
            $diagnosticsLines[] = 'Branch: ' . ($user->branch?->name ?? 'Head Office');
            $diagnosticsLines[] = 'App: ' . config('app.name');
            $diagnosticsLines[] = 'Laravel: ' . app()->version();
            $diagnosticsLines[] = 'PHP: ' . PHP_VERSION;
            $diagnosticsLines[] = 'Queue: ' . config('queue.default');
            $diagnosticsLines[] = 'Realtime enabled: ' . (config('app.realtime_enabled') ? 'true' : 'false');

            try {
                if (config('queue.default') === 'database') {
                    $diagnosticsLines[] = 'Jobs pending: ' . (string) DB::table('jobs')->count();
                }
                $diagnosticsLines[] = 'Jobs failed: ' . (string) DB::table('failed_jobs')->count();
            } catch (\Throwable $exception) {
                $diagnosticsLines[] = 'Jobs: unavailable';
            }

            $diagnosticsText = implode("\n", $diagnosticsLines);
            $description .= '<p><strong>Diagnostics</strong></p><p>' . nl2br(e($diagnosticsText)) . '</p>';
        }

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

        $rolesToNotify = [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER];
        if ($user && $user->role === User::ROLE_SUPER_ADMIN) {
            $rolesToNotify = [User::ROLE_SUPER_ADMIN];
        }

        $recipients = User::query()
            ->whereIn('role', $rolesToNotify)
            ->get();
        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, SupportTicketCreated::fromTicket($ticket));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $developerEmailNote = null;
        if ($isDeveloperTicket) {
            $supportEmail = null;
            try {
                $supportEmail = AppSetting::getValue('support_email');
            } catch (\Throwable $exception) {
                $supportEmail = null;
            }

            $supportEmail = is_string($supportEmail) ? trim($supportEmail) : '';

            if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    $ticket->loadMissing('attachments');
                    $attachments = $ticket->attachments
                        ->map(fn ($a) => [
                            'path' => $a->path,
                            'name' => $a->original_name,
                            'mime' => $a->mime_type,
                        ])
                        ->values()
                        ->all();

                    $payload = [
                        'ticket_id' => $ticket->id,
                        'subject' => $ticket->subject,
                        'description' => $descriptionPlain,
                        'requester_name' => (string) ($user->name ?? ''),
                        'requester_email' => (string) ($user->email ?? ''),
                        'branch_name' => $user->branch?->name,
                        'priority' => $ticket->priority,
                        'status' => $ticket->status,
                        'link' => route('helpdesk.show', $ticket),
                        'diagnostics' => $diagnosticsText,
                        'attachments' => $attachments,
                    ];
                    Notification::route('mail', $supportEmail)
                        ->notify(DeveloperSupportTicketCreated::fromPayload($payload));
                    $developerEmailNote = 'Developer email sent to ' . $supportEmail . '.';
                } catch (\Throwable $exception) {
                    report($exception);
                    $developerEmailNote = 'Ticket created, but sending the developer email failed. Please check your mail settings and logs.';
                }
            } else {
                $developerEmailNote = 'Ticket created, but no developer email was sent because “Support/Developer email” is not set or invalid (Profile → App Settings).';
            }
        }

        $redirect = redirect()
            ->route('helpdesk.show', $ticket)
            ->with('success', 'Support ticket submitted.');

        if ($developerEmailNote) {
            $redirect->with('warning', $developerEmailNote);
        }

        return $redirect;
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->ensureHelpDeskEnabled();
        $this->authorizeTicket($ticket, $request->user());

        $ticket->load(['user', 'branch', 'attachments', 'messages.user', 'messages.attachments']);

        return view('helpdesk.show_v2', compact('ticket'));
    }

    public function latestMessages(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->ensureHelpDeskEnabled();
        $this->authorizeTicket($ticket, $request->user());

        $after = (int) $request->query('after', 0);
        $lastId = (int) $ticket->messages()->max('id');

        if ($lastId === 0 || $after >= $lastId) {
            return response()->json([
                'unchanged' => true,
                'last_id' => $lastId,
            ]);
        }

        $ticket->load(['messages.user', 'messages.attachments']);

        return response()->json([
            'last_id' => $lastId,
            'html' => view('helpdesk._messages', [
                'ticket' => $ticket,
                'currentUser' => $request->user(),
            ])->render(),
        ]);
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

        $messageAttachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('helpdesk', 'local');
            $attachment = SupportTicketAttachment::create([
                'support_ticket_id' => $ticket->id,
                'support_ticket_message_id' => $message->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
            $messageAttachments[] = [
                'path' => $attachment->path,
                'name' => $attachment->original_name,
                'mime' => $attachment->mime_type,
            ];
        }

        $recipients = $this->replyRecipients($ticket, $request->user());
        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, SupportTicketReply::fromMessage($ticket, $message));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($ticket->category === SupportTicket::CATEGORY_DEVELOPER) {
            $supportEmail = null;
            try {
                $supportEmail = AppSetting::getValue('support_email');
            } catch (\Throwable $exception) {
                $supportEmail = null;
            }
            $supportEmail = is_string($supportEmail) ? trim($supportEmail) : '';

            if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    $ticket->loadMissing('user');
                    $message->loadMissing('user');
                    $devMail = DeveloperSupportTicketMessage::fromTicketAndMessage($ticket, $message, $messageAttachments);
                    Notification::route('mail', $supportEmail)->notify($devMail);
                } catch (\Throwable $exception) {
                    report($exception);
                }
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
