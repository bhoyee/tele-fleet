<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestRejected extends Notification
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
        $tripTime = $this->tripRequest->trip_time
            ? \Illuminate\Support\Carbon::createFromFormat('H:i', $this->tripRequest->trip_time)->format('g:i A')
            : 'N/A';

        return (new MailMessage)
            ->subject('Trip Request Rejected '.$this->tripRequest->request_number)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your trip request has been reviewed and rejected.')
            ->line('Request: '.$this->tripRequest->request_number)
            ->line('Purpose: '.$this->tripRequest->purpose)
            ->line('Destination: '.$this->tripRequest->destination)
            ->line('Trip Date: '.$this->tripRequest->trip_date?->format('M d, Y'))
            ->line('Trip Time: '.$tripTime)
            ->line('Reason: '.$this->tripRequest->rejection_reason)
            ->action('View Trip Request', route('trips.show', $this->tripRequest))
            ->line('If you need clarification, please contact the fleet team.');
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
            'rejection_reason' => $this->tripRequest->rejection_reason,
        ];
    }
}
