<?php

namespace App\Notifications;

use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\Vehicle;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestReassigned extends Notification
{
    use Queueable;
    use SkipsInvalidMailRecipients;

    private ?Vehicle $fromVehicle = null;
    private ?Driver $fromDriver = null;

    public function __construct(
        private TripRequest $tripRequest,
        private ?int $fromVehicleId,
        private ?int $fromDriverId,
        private string $reason
    ) {
        if ($this->fromVehicleId) {
            $this->fromVehicle = Vehicle::find($this->fromVehicleId);
        }
        if ($this->fromDriverId) {
            $this->fromDriver = Driver::find($this->fromDriverId);
        }
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fromVehicle = $this->fromVehicle?->registration_number ?? 'N/A';
        $fromDriver = $this->fromDriver?->full_name ?? 'N/A';
        $toVehicle = $this->tripRequest->assignedVehicle?->registration_number ?? 'N/A';
        $toDriver = $this->tripRequest->assignedDriver?->full_name ?? 'N/A';
        $toDriverPhone = $this->tripRequest->assignedDriver?->phone ?? 'N/A';
        $condition = trim((string) ($this->tripRequest->condition_notes ?? ''));

        $mail = (new MailMessage)
            ->subject('Trip Reassigned '.$this->tripRequest->request_number)
            ->line('A trip assignment has been changed.')
            ->line('Previous Vehicle: '.$fromVehicle)
            ->line('Previous Driver: '.$fromDriver)
            ->line('New Vehicle: '.$toVehicle)
            ->line('New Driver: '.$toDriver)
            ->line('New Driver Phone: '.$toDriverPhone)
            ->line('Reason: '.$this->reason)
            ->action('View Trip Request', route('trips.show', $this->tripRequest));

        if ($condition !== '') {
            $mail->line('Condition: '.$condition);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'trip_request_uuid' => $this->tripRequest->uuid ?? null,
            'request_number' => $this->tripRequest->request_number,
            'status' => $this->tripRequest->status,
            'from_vehicle' => $this->fromVehicle?->registration_number,
            'from_driver' => $this->fromDriver?->full_name,
            'assigned_vehicle' => $this->tripRequest->assignedVehicle?->registration_number,
            'assigned_driver' => $this->tripRequest->assignedDriver?->full_name,
            'condition_notes' => $this->tripRequest->condition_notes,
            'reason' => $this->reason,
            'reassigned_at' => now()->toDateTimeString(),
        ];
    }
}
