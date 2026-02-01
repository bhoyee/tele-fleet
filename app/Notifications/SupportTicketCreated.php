<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class SupportTicketCreated extends Notification
{
    public function __construct(private SupportTicket $ticket)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New support ticket: ' . $this->ticket->subject)
            ->greeting('Hello!')
            ->line('A new support ticket was submitted.')
            ->line('Subject: ' . $this->ticket->subject)
            ->line('Category: ' . ucfirst($this->ticket->category))
            ->line('Priority: ' . ucfirst($this->ticket->priority))
            ->action('View Ticket', route('helpdesk.show', $this->ticket))
            ->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'status' => $this->ticket->status,
            'priority' => $this->ticket->priority,
        ];
    }
}
