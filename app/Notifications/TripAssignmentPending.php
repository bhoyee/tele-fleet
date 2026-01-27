<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripAssignmentPending extends Notification
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
        $tripDate = $this->tripRequest->trip_date?->format('M d, Y') ?? 'N/A';
        $tripTime = $this->tripRequest->trip_time
            ? \Illuminate\Support\Carbon::parse($this->tripRequest->trip_time)->format('g:i A')
            : 'N/A';

        return (new MailMessage)
            ->subject('Trip Pending Assignment '.$this->tripRequest->request_number)
            ->line('This trip is approved but has no driver or vehicle assigned.')
            ->line('Trip Date: '.$tripDate)
            ->line('Trip Time: '.$tripTime)
            ->action('Review Trip Request', route('trips.show', $this->tripRequest));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'request_number' => $this->tripRequest->request_number,
            'status' => $this->tripRequest->status,
            'trip_date' => $this->tripRequest->trip_date?->toDateString(),
            'trip_time' => $this->tripRequest->trip_time,
        ];
    }
}
