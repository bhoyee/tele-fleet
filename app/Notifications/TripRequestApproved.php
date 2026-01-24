<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestApproved extends Notification
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
            ->subject('Trip Approved '.$this->tripRequest->request_number)
            ->line('Your trip request has been approved.')
            ->line('Purpose: '.$this->tripRequest->purpose)
            ->line('Destination: '.$this->tripRequest->destination)
            ->action('View Trip Request', route('trips.show', $this->tripRequest));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'request_number' => $this->tripRequest->request_number,
            'status' => $this->tripRequest->status,
            'approved_at' => $this->tripRequest->approved_at?->toDateTimeString(),
        ];
    }
}
