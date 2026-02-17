<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentReported extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;

    use SkipsInvalidMailRecipients;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $incidentId,
        private string $reference,
        private string $severity,
        private string $status,
    )
    {
    }

    public static function fromIncident(IncidentReport $incident): self
    {
        return (new self(
            incidentId: (int) $incident->id,
            reference: (string) $incident->reference,
            severity: (string) $incident->severity,
            status: (string) $incident->status,
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
        return (new MailMessage())
            ->subject('Incident Reported: ' . $this->reference)
            ->greeting('Hello ' . ($notifiable->name ?? '') . ',')
            ->line('A new incident has been reported.')
            ->line('Reference: ' . $this->reference)
            ->line('Severity: ' . ucfirst($this->severity))
            ->action('View Incident', route('incidents.show', $this->incidentId))
            ->line('Please review and take action as needed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incidentId,
            'reference' => $this->reference,
            'severity' => $this->severity,
            'status' => $this->status,
        ];
    }
}
