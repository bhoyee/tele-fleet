<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DeveloperSupportTicketMessage extends Notification
{
    use Queueable;

    public function __construct(
        private int $ticketId,
        private string $ticketCode,
        private string $subject,
        private string $fromLabel,
        private string $messageText,
        private array $attachments,
    )
    {
    }

    public static function fromTicketAndMessage(SupportTicket $ticket, SupportTicketMessage $message, array $attachments): self
    {
        $ticketCode = 'TCK-' . str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);
        $fromLabel = $message->user?->name
            ? ($message->user->name . ' (' . ($message->user->email ?? '') . ')')
            : trim(($message->external_name ?: 'Sender') . ($message->external_email ? (' <' . $message->external_email . '>') : ''));

        $plain = trim(strip_tags((string) $message->message));

        return new self(
            ticketId: (int) $ticket->id,
            ticketCode: $ticketCode,
            subject: (string) $ticket->subject,
            fromLabel: $fromLabel !== '' ? $fromLabel : 'Sender',
            messageText: $plain !== '' ? $plain : '(No message content)',
            attachments: $attachments,
        );
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brandName = config('app.name', 'Tele-Fleet');
        $replyLink = URL::temporarySignedRoute(
            'developer-support.reply',
            now()->addDays(30),
            ['ticket' => $this->ticketId]
        );

        $mail = (new MailMessage)
            ->subject('[' . $this->ticketCode . '] ' . $this->subject)
            ->greeting('Hello,')
            ->line('New message on developer support ticket ' . $this->ticketCode . '.')
            ->line('From: ' . $this->fromLabel)
            ->line('Message:')
            ->line($this->messageText)
            ->line('Use the secure reply link below to respond (no login required).')
            ->action('Reply to Ticket', $replyLink)
            ->line('Or reply to this email (keep ' . $this->ticketCode . ' in the subject).');

        $replyToAddress = trim((string) env('SUPPORT_INBOX_USERNAME', ''));
        if ($replyToAddress !== '' && filter_var($replyToAddress, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($replyToAddress, $brandName . ' Support');
        }

        foreach ($this->attachments as $attachment) {
            $path = (string) ($attachment['path'] ?? '');
            if ($path === '' || ! Storage::disk('local')->exists($path)) {
                continue;
            }
            $absolutePath = Storage::disk('local')->path($path);
            $name = (string) ($attachment['name'] ?? 'attachment');
            $mime = (string) ($attachment['mime'] ?? '');

            $options = ['as' => $name];
            if ($mime !== '') {
                $options['mime'] = $mime;
            }

            try {
                $mail->attach($absolutePath, $options);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $mail->line('-- ' . $brandName);
    }
}
