<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use App\Models\User;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentUpdated extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public function __construct(private IncidentReport $incident, private User $updatedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Incident Updated: ' . $this->incident->reference)
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('An incident report has been updated.')
            ->line('Reference: ' . $this->incident->reference)
            ->line('Updated By: ' . ($this->updatedBy->name ?? 'System'))
            ->action('View Incident', route('incidents.show', $this->incident))
            ->line('Please review the latest details.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'reference' => $this->incident->reference,
            'status' => $this->incident->status,
            'updated_by' => $this->updatedBy->name ?? null,
        ];
    }
}
