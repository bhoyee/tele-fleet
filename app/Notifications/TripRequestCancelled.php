<?php

namespace App\Notifications;

use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private TripRequest $tripRequest, private User $cancelledBy)
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
            ? \Illuminate\Support\Carbon::createFromFormat('H:i', $this->tripRequest->trip_time)->format('g:i A')
            : 'N/A';

        return (new MailMessage)
            ->subject('Trip Request Cancelled - ' . $this->tripRequest->request_number)
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A trip request has been cancelled.')
            ->line('Request Number: ' . $this->tripRequest->request_number)
            ->line('Purpose: ' . ($this->tripRequest->purpose ?? 'N/A'))
            ->line('Destination: ' . ($this->tripRequest->destination ?? 'N/A'))
            ->line('Trip Date: ' . $tripDate)
            ->line('Trip Time: ' . $tripTime)
            ->line('Cancelled By: ' . ($this->cancelledBy->name ?? 'System'))
            ->action('View Trip', route('trips.show', $this->tripRequest))
            ->line('If you have questions, please contact your fleet manager.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'request_number' => $this->tripRequest->request_number,
            'purpose' => $this->tripRequest->purpose,
            'destination' => $this->tripRequest->destination,
            'trip_date' => $this->tripRequest->trip_date?->toDateString(),
            'trip_time' => $this->tripRequest->trip_time,
            'cancelled_by' => $this->cancelledBy->name ?? null,
            'status' => 'cancelled',
        ];
    }
}
