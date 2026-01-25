<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentReported extends Notification
{
    use Queueable;

    public function __construct(private readonly IncidentReport $incident)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Incident Reported: ' . $this->incident->reference)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new incident has been reported.')
            ->line('Reference: ' . $this->incident->reference)
            ->line('Severity: ' . ucfirst($this->incident->severity))
            ->action('View Incident', route('incidents.show', $this->incident))
            ->line('Please review and take action as needed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'reference' => $this->incident->reference,
            'severity' => $this->incident->severity,
            'status' => $this->incident->status,
        ];
    }
}
