<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class DeveloperSupportTicketCreated extends Notification
{
    use Queueable;

    public function __construct(
        private int $ticketId,
        private string $subject,
        private string $descriptionText,
        private string $requesterName,
        private string $requesterEmail,
        private ?string $branchName,
        private string $priority,
        private string $status,
        private string $link,
        private string $diagnosticsText,
        private array $attachments,
    )
    {
    }

    public static function fromPayload(array $payload): self
    {
        return (new self(
            ticketId: (int) ($payload['ticket_id'] ?? 0),
            subject: (string) ($payload['subject'] ?? ''),
            descriptionText: (string) ($payload['description'] ?? ''),
            requesterName: (string) ($payload['requester_name'] ?? ''),
            requesterEmail: (string) ($payload['requester_email'] ?? ''),
            branchName: $payload['branch_name'] ?? null,
            priority: (string) ($payload['priority'] ?? ''),
            status: (string) ($payload['status'] ?? ''),
            link: (string) ($payload['link'] ?? ''),
            diagnosticsText: (string) ($payload['diagnostics'] ?? ''),
            attachments: (array) ($payload['attachments'] ?? []),
        ));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticketCode = 'TCK-' . str_pad((string) $this->ticketId, 5, '0', STR_PAD_LEFT);
        $brandName = config('app.name', 'Tele-Fleet');
        $replyLink = URL::temporarySignedRoute(
            'developer-support.reply',
            now()->addDays(30),
            ['ticket' => $this->ticketId]
        );

        $mail = (new MailMessage)
            ->subject('Developer Support: ' . $ticketCode . ' - ' . $this->subject)
            ->greeting('Hello,')
            ->line('A Super Admin submitted a developer support ticket.')
            ->line('Ticket: ' . $ticketCode)
            ->line('Priority: ' . ucfirst($this->priority))
            ->line('Status: ' . ucfirst(str_replace('_', ' ', $this->status)))
            ->line('Requester: ' . $this->requesterName . ' (' . $this->requesterEmail . ')');

        if ($this->branchName) {
            $mail->line('Branch: ' . $this->branchName);
        }

        if (trim($this->descriptionText) !== '') {
            $mail->line('Message:')->line($this->descriptionText);
        }

        if ($this->diagnosticsText !== '') {
            $mail->line('Diagnostics:')->line($this->diagnosticsText);
        }

        if ($this->link !== '') {
            $mail->action('View Ticket', $this->link);
        }

        $mail->line('Use the secure reply link below to respond (no login required).')
            ->action('Reply to Ticket', $replyLink)
            ->line('Or reply to this email (keep the ticket code in the subject): ' . $ticketCode . '.');

        $replyToAddress = trim((string) env('SUPPORT_INBOX_USERNAME', ''));
        if ($replyToAddress !== '' && filter_var($replyToAddress, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($replyToAddress, $brandName . ' Support');
        }

        foreach ($this->attachments as $attachment) {
            $path = (string) ($attachment['path'] ?? '');
            if ($path === '') {
                continue;
            }
            $name = (string) ($attachment['name'] ?? 'attachment');
            $mime = (string) ($attachment['mime'] ?? '');
            try {
                if (! Storage::disk('local')->exists($path)) {
                    continue;
                }

                $absolutePath = Storage::disk('local')->path($path);
                $options = ['as' => $name];
                if ($mime !== '') {
                    $options['mime'] = $mime;
                }

                $mail->attach($absolutePath, $options);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $mail->line('-- ' . $brandName);
    }
}
