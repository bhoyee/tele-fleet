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

class IncidentStatusUpdated extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject('Incident Status Updated: ' . $this->incident->reference)
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('The incident status has been updated.')
            ->line('Reference: ' . $this->incident->reference)
            ->line('New Status: ' . str_replace('_', ' ', ucfirst($this->incident->status)))
            ->line('Updated By: ' . ($this->updatedBy->name ?? 'System'));

        if (! empty($this->incident->resolution_notes)) {
            $mail->line('Resolution Notes: ' . $this->incident->resolution_notes);
        }

        $mail->action('View Incident', route('incidents.show', $this->incident))
            ->line('Thank you.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'incident_uuid' => $this->incident->uuid ?? null,
            'reference' => $this->incident->reference,
            'status' => $this->incident->status,
            'updated_by' => $this->updatedBy->name ?? null,
            'resolution_notes' => $this->incident->resolution_notes,
        ];
    }
}
