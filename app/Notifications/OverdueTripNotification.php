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
        $routeKey = $this->trip->uuid;
        if (! is_string($routeKey) || $routeKey === '') {
            $trip = TripRequest::withTrashed()->find($this->trip->getKey());
            $routeKey = is_string($trip?->uuid ?? null) && $trip->uuid !== ''
                ? $trip->uuid
                : $this->trip->getKey();
        }

        return (new MailMessage())
            ->subject('Trip Overdue - Update Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A trip assignment has been active for over 7 days and needs a status update.')
            ->line('Trip: ' . ($this->trip->request_number ?? 'N/A'))
            ->action('Review Trip', route('trips.show', $routeKey));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->trip->id,
            'trip_request_uuid' => $this->trip->uuid ?? null,
            'request_number' => $this->trip->request_number,
            'type' => 'overdue_trip',
        ];
    }
}
