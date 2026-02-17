<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\AppSetting;
use App\Models\User;
use App\Notifications\SupportTicketDeveloperReply;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class FetchSupportInboxReplies extends Command
{
    protected $signature = 'telefleet:fetch-support-replies';

    protected $description = 'Fetch developer replies from the support inbox (IMAP) and append them to developer support tickets.';

    public function handle(): int
    {
        if (! (bool) env('SUPPORT_INBOX_ENABLED', false)) {
            $this->line('Support inbox fetching is disabled (SUPPORT_INBOX_ENABLED=false).');
            return self::SUCCESS;
        }

        if (! function_exists('imap_open')) {
            $this->warn('PHP IMAP extension is not installed/enabled. Cannot fetch email replies.');
            return self::SUCCESS;
        }

        $host = (string) env('SUPPORT_INBOX_HOST', '');
        $port = (string) env('SUPPORT_INBOX_PORT', '993');
        $encryption = strtolower((string) env('SUPPORT_INBOX_ENCRYPTION', 'ssl'));
        $username = (string) env('SUPPORT_INBOX_USERNAME', '');
        $password = (string) env('SUPPORT_INBOX_PASSWORD', '');
        $mailbox = (string) env('SUPPORT_INBOX_MAILBOX', 'INBOX');

        if ($host === '' || $username === '' || $password === '') {
            $this->warn('Missing SUPPORT_INBOX_HOST/USERNAME/PASSWORD env vars.');
            return self::SUCCESS;
        }

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        } elseif ($encryption === 'none') {
            $flags .= '/notls';
        }

        $mailboxString = sprintf('{%s:%s%s}%s', $host, $port, $flags, $mailbox);

        $inbox = @imap_open($mailboxString, $username, $password);
        if (! $inbox) {
            $this->warn('Failed to connect to inbox via IMAP.');
            return self::SUCCESS;
        }

        $lastUid = 0;
        try {
            $lastUid = (int) AppSetting::getValue('support_inbox_last_uid', 0);
        } catch (\Throwable $exception) {
            $lastUid = 0;
        }

        $allUids = imap_search($inbox, 'ALL', SE_UID);
        $maxUidAll = is_array($allUids) && count($allUids) ? max(array_map('intval', $allUids)) : 0;

        // If we have never run before, import UNSEEN messages once, then baseline the cursor to max UID
        // to avoid missing subsequent replies that arrive already marked as "Seen" by the mailbox provider.
        if ($lastUid <= 0) {
            $emails = imap_search($inbox, 'UNSEEN', SE_UID);
        } else {
            $emails = imap_search($inbox, 'UID ' . ($lastUid + 1) . ':*', SE_UID);
        }

        if (! is_array($emails) || count($emails) === 0) {
            // Still advance cursor to current max so next run only checks new mail.
            if ($maxUidAll > 0 && $lastUid <= 0) {
                try {
                    AppSetting::setValue('support_inbox_last_uid', (string) $maxUidAll);
                } catch (\Throwable $exception) {
                }
            }
            imap_close($inbox);
            $this->line('No new emails.');
            return self::SUCCESS;
        }

        $emails = array_values(array_unique(array_map('intval', $emails)));
        sort($emails);

        $processed = 0;
        $maxProcessedUid = $lastUid;

        foreach ($emails as $uid) {
            $msgNo = imap_msgno($inbox, (int) $uid);
            if (! $msgNo) {
                $maxProcessedUid = max($maxProcessedUid, (int) $uid);
                continue;
            }

            $header = imap_headerinfo($inbox, (int) $msgNo);
            $subjectRaw = (string) ($header->subject ?? '');
            $subject = trim((string) imap_utf8($subjectRaw));

            $ticketId = null;
            if (preg_match('/TCK-(\\d{5})/i', $subject, $m)) {
                $ticketId = (int) ltrim($m[1], '0');
            } else {
                // Fallback: try finding ticket code in body (some mail clients change/remove subject).
                $bodyForMatch = $this->bestEffortBody($inbox, (int) $msgNo);
                if (preg_match('/TCK-(\\d{5})/i', $bodyForMatch, $m2)) {
                    $ticketId = (int) ltrim($m2[1], '0');
                }
            }

            if (! $ticketId || $ticketId <= 0) {
                $maxProcessedUid = max($maxProcessedUid, (int) $uid);
                continue;
            }

            $ticket = SupportTicket::query()->with('user')->find($ticketId);
            if (! $ticket || $ticket->category !== SupportTicket::CATEGORY_DEVELOPER) {
                $maxProcessedUid = max($maxProcessedUid, (int) $uid);
                continue;
            }

            $fromEmail = '';
            $fromName = '';
            if (! empty($header->from) && is_array($header->from) && isset($header->from[0])) {
                $from = $header->from[0];
                $fromEmail = trim(((string) ($from->mailbox ?? '')) . '@' . ((string) ($from->host ?? '')));
                $fromName = trim((string) ($from->personal ?? ''));
            }

            $body = $this->bestEffortBody($inbox, (int) $msgNo);
            $bodyText = trim($body);
            if ($bodyText === '') {
                $bodyText = '(No message content)';
            }

            $messageHtml = nl2br(e($bodyText));
            $messageHtml = strip_tags($messageHtml, '<br>');
            $messageHtml = '<p>' . $messageHtml . '</p>';

            $message = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => null,
                'external_name' => $fromName !== '' ? $fromName : 'Developer',
                'external_email' => $fromEmail !== '' ? $fromEmail : null,
                'message' => $messageHtml,
            ]);

            $this->saveAttachments($inbox, (int) $msgNo, $ticket->id, $message->id);

            $recipients = collect();
            if ($ticket->user) {
                $recipients->push($ticket->user);
            }
            // Also notify all super admins (in case ticket owner changed).
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

            $processed++;
            $maxProcessedUid = max($maxProcessedUid, (int) $uid);
        }

        // Advance cursor.
        if ($maxUidAll > 0 && $lastUid <= 0) {
            $maxProcessedUid = max($maxProcessedUid, $maxUidAll);
        }

        if ($maxProcessedUid > 0) {
            try {
                AppSetting::setValue('support_inbox_last_uid', (string) $maxProcessedUid);
            } catch (\Throwable $exception) {
            }
        }

        imap_close($inbox);

        $this->info("Processed {$processed} email(s).");
        return self::SUCCESS;
    }

    private function bestEffortBody($inbox, int $emailNumber): string
    {
        // Try text/plain first.
        $plain = imap_fetchbody($inbox, $emailNumber, '1');
        if (is_string($plain) && trim($plain) !== '') {
            return $this->decodeBody($plain);
        }

        $alt = imap_fetchbody($inbox, $emailNumber, '1.1');
        if (is_string($alt) && trim($alt) !== '') {
            return $this->decodeBody($alt);
        }

        $body = imap_body($inbox, $emailNumber);
        return is_string($body) ? $this->decodeBody($body) : '';
    }

    private function decodeBody(string $body): string
    {
        // imap_* returns raw; do a light decode.
        $decoded = quoted_printable_decode($body);
        $decoded = preg_replace("/\\r\\n|\\r/", "\n", (string) $decoded);
        return (string) $decoded;
    }

    private function saveAttachments($inbox, int $emailNumber, int $ticketId, int $messageId): void
    {
        $structure = imap_fetchstructure($inbox, $emailNumber);
        if (! $structure) {
            return;
        }

        $parts = [];
        if (! empty($structure->parts) && is_array($structure->parts)) {
            $parts = $structure->parts;
        } else {
            // Single-part message.
            $parts = [];
        }

        if (empty($parts)) {
            return;
        }

        $this->walkPartsAndSave($inbox, $emailNumber, $parts, '', $ticketId, $messageId);
    }

    private function walkPartsAndSave($inbox, int $emailNumber, array $parts, string $prefix, int $ticketId, int $messageId): void
    {
        foreach ($parts as $index => $part) {
            $partNumber = $prefix === '' ? (string) ($index + 1) : ($prefix . '.' . ($index + 1));

            if (! empty($part->parts) && is_array($part->parts)) {
                $this->walkPartsAndSave($inbox, $emailNumber, $part->parts, $partNumber, $ticketId, $messageId);
            }

            $attachmentName = $this->partFilename($part);
            if (! $attachmentName) {
                continue;
            }

            $data = imap_fetchbody($inbox, $emailNumber, $partNumber);
            if (! is_string($data) || $data === '') {
                continue;
            }

            $decoded = $this->decodePartData($data, (int) ($part->encoding ?? 0));
            if ($decoded === '') {
                continue;
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $attachmentName) ?: 'attachment';
            $path = 'helpdesk/inbound-' . $ticketId . '-' . uniqid('', true) . '-' . $safeName;
            Storage::disk('local')->put($path, $decoded);

            SupportTicketAttachment::create([
                'support_ticket_id' => $ticketId,
                'support_ticket_message_id' => $messageId,
                'path' => $path,
                'original_name' => $attachmentName,
                'mime_type' => $this->partMime($part),
                'size' => strlen($decoded),
            ]);
        }
    }

    private function partFilename(object $part): ?string
    {
        if (! empty($part->dparameters) && is_array($part->dparameters)) {
            foreach ($part->dparameters as $p) {
                if (! empty($p->attribute) && strtolower((string) $p->attribute) === 'filename') {
                    return (string) imap_utf8((string) $p->value);
                }
            }
        }
        if (! empty($part->parameters) && is_array($part->parameters)) {
            foreach ($part->parameters as $p) {
                if (! empty($p->attribute) && strtolower((string) $p->attribute) === 'name') {
                    return (string) imap_utf8((string) $p->value);
                }
            }
        }
        return null;
    }

    private function partMime(object $part): ?string
    {
        $type = (int) ($part->type ?? 0);
        $subtype = strtolower((string) ($part->subtype ?? ''));
        $main = match ($type) {
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            default => 'application',
        };
        return $subtype !== '' ? ($main . '/' . $subtype) : null;
    }

    private function decodePartData(string $data, int $encoding): string
    {
        return match ($encoding) {
            3 => (string) base64_decode($data, true),
            4 => (string) quoted_printable_decode($data),
            default => $data,
        } ?: '';
    }
}
