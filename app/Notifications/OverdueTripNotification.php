<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueTripNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly TripRequest $trip)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Trip Overdue - Update Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A trip assignment has been active for over 7 days and needs a status update.')
            ->line('Trip: ' . ($this->trip->request_number ?? 'N/A'))
            ->action('Review Trip', route('trips.show', $this->trip));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->trip->id,
            'request_number' => $this->trip->request_number,
            'type' => 'overdue_trip',
        ];
    }
}
