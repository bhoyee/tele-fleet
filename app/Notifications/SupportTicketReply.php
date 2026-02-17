<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Notifications\Concerns\QueueReliability;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReply extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $ticketId,
        private string $subject,
        private string $priority,
        private string $status,
        private ?string $messageExcerpt = null,
    )
    {
    }

    public static function fromMessage(SupportTicket $ticket, SupportTicketMessage $message): self
    {
        $plain = trim(strip_tags((string) $message->message));
        $excerpt = $plain !== '' ? mb_substr($plain, 0, 140) : null;

        return new self(
            ticketId: (int) $ticket->id,
            subject: (string) $ticket->subject,
            priority: (string) $ticket->priority,
            status: (string) $ticket->status,
            messageExcerpt: $excerpt,
        );
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
            ->subject('Support ticket reply: ' . $this->subject)
            ->greeting('Hello!')
            ->line('There is a new reply on support ticket TCK-' . str_pad((string) $this->ticketId, 5, '0', STR_PAD_LEFT) . '.')
            ->line('Subject: ' . $this->subject);

        if ($this->messageExcerpt) {
            $mail->line('Message: ' . $this->messageExcerpt);
        }

        return $mail
            ->action('View Ticket', route('helpdesk.show', $this->ticketId))
            ->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'subject' => $this->subject,
            'status' => $this->status,
            'priority' => $this->priority,
        ];
    }
}
