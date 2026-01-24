<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestCreated extends Notification
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
        return (new MailMessage)
            ->subject('New Trip Request '.$this->tripRequest->request_number)
            ->line('A new trip request has been submitted.')
            ->line('Purpose: '.$this->tripRequest->purpose)
            ->line('Destination: '.$this->tripRequest->destination)
            ->action('View Trip Request', route('trips.show', $this->tripRequest))
            ->line('Please review and proceed with approval.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'request_number' => $this->tripRequest->request_number,
            'status' => $this->tripRequest->status,
            'purpose' => $this->tripRequest->purpose,
            'destination' => $this->tripRequest->destination,
            'trip_date' => $this->tripRequest->trip_date?->toDateString(),
        ];
    }
}
