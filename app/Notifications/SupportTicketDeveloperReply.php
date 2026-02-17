<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketDeveloperReply extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $ticketId,
        private string $ticketCode,
        private string $subject,
        private string $fromLabel,
        private ?string $messageExcerpt,
    )
    {
    }

    public static function fromInbound(SupportTicket $ticket, SupportTicketMessage $message): self
    {
        $fromLabel = $message->external_email
            ? trim(($message->external_name ?: 'Developer') . ' <' . $message->external_email . '>')
            : ($message->external_name ?: 'Developer');

        $plain = trim(strip_tags((string) $message->message));
        $excerpt = $plain !== '' ? mb_substr($plain, 0, 180) : null;

        return (new self(
            ticketId: (int) $ticket->id,
            ticketCode: 'TCK-' . str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
            subject: (string) $ticket->subject,
            fromLabel: $fromLabel,
            messageExcerpt: $excerpt,
        ))
            ->onConnection('database')
            ->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Developer replied: ' . $this->ticketCode)
            ->line('You received a reply from the developer on your support ticket.')
            ->line('Ticket: ' . $this->ticketCode)
            ->line('Subject: ' . $this->subject)
            ->line('From: ' . $this->fromLabel);

        if ($this->messageExcerpt) {
            $mail->line('Message: ' . $this->messageExcerpt);
        }

        return $mail->action('View Ticket', route('helpdesk.show', $this->ticketId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'ticket_code' => $this->ticketCode,
            'from' => $this->fromLabel,
        ];
    }
}

