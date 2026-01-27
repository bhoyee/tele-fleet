<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private IncidentReport $incident, private User $updatedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Incident Status Updated: ' . $this->incident->reference)
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('The incident status has been updated.')
            ->line('Reference: ' . $this->incident->reference)
            ->line('New Status: ' . str_replace('_', ' ', ucfirst($this->incident->status)))
            ->line('Updated By: ' . ($this->updatedBy->name ?? 'System'))
            ->action('View Incident', route('incidents.show', $this->incident))
            ->line('Thank you.');
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
