<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestAssigned extends Notification
{
    use Queueable;

    public function __construct(private TripRequest $tripRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vehicle = $this->tripRequest->assignedVehicle?->registration_number ?? 'N/A';
        $driver = $this->tripRequest->assignedDriver?->full_name ?? 'N/A';

        return (new MailMessage)
            ->subject('Trip Assigned '.$this->tripRequest->request_number)
            ->line('Your trip request has been assigned.')
            ->line('Vehicle: '.$vehicle)
            ->line('Driver: '.$driver)
            ->action('View Trip Request', route('trips.show', $this->tripRequest));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'request_number' => $this->tripRequest->request_number,
            'status' => $this->tripRequest->status,
            'assigned_vehicle' => $this->tripRequest->assignedVehicle?->registration_number,
            'assigned_driver' => $this->tripRequest->assignedDriver?->full_name,
            'assigned_at' => $this->tripRequest->assigned_at?->toDateTimeString(),
        ];
    }
}
