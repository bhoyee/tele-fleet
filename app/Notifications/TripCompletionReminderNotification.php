<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripCompletionReminderNotification extends Notification
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
            ->subject('Trip Completion Reminder')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A trip assignment is still open 24 hours after its trip date.')
            ->line('Trip: ' . ($this->trip->request_number ?? 'N/A'))
            ->action('Review Trip', route('trips.show', $this->trip));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->trip->id,
            'request_number' => $this->trip->request_number,
            'type' => 'trip_completion_reminder',
        ];
    }
}
