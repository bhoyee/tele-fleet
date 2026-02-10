<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * If the underlying ticket was deleted before this queued notification runs,
     * drop the job instead of keeping it in failed_jobs forever.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $ticketId,
        private string $subject,
        private string $category,
        private string $priority,
        private string $status,
    )
    {
    }

    public static function fromTicket(SupportTicket $ticket): self
    {
        return new self(
            ticketId: (int) $ticket->id,
            subject: (string) $ticket->subject,
            category: (string) $ticket->category,
            priority: (string) $ticket->priority,
            status: (string) $ticket->status,
        );
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New support ticket: ' . $this->subject)
            ->greeting('Hello!')
            ->line('A new support ticket was submitted.')
            ->line('Ticket: TCK-' . str_pad((string) $this->ticketId, 5, '0', STR_PAD_LEFT))
            ->line('Subject: ' . $this->subject)
            ->line('Category: ' . ucfirst($this->category))
            ->line('Priority: ' . ucfirst($this->priority))
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
