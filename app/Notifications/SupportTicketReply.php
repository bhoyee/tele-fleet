<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class SupportTicketReply extends Notification
{
    public function __construct(private SupportTicket $ticket, private SupportTicketMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Support ticket reply: ' . $this->ticket->subject)
            ->greeting('Hello!')
            ->line('There is a new reply on support ticket TCK-' . str_pad($this->ticket->id, 5, '0', STR_PAD_LEFT) . '.')
            ->line('Subject: ' . $this->ticket->subject)
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
