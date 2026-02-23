<?php

namespace App\Notifications;

use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestCancelled extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public function __construct(private TripRequest $tripRequest, private User $cancelledBy)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tripDate = $this->tripRequest->trip_date?->format('M d, Y') ?? 'N/A';
        $tripTime = 'N/A';
        if ($this->tripRequest->trip_time) {
            try {
                $tripTime = \Illuminate\Support\Carbon::parse($this->tripRequest->trip_time)->format('g:i A');
            } catch (\Throwable) {
                $tripTime = (string) $this->tripRequest->trip_time;
            }
        }

        $routeKey = $this->tripRequest->uuid;
        if (! is_string($routeKey) || $routeKey === '') {
            $trip = TripRequest::withTrashed()->find($this->tripRequest->getKey());
            $routeKey = is_string($trip?->uuid ?? null) && $trip->uuid !== ''
                ? $trip->uuid
                : $this->tripRequest->getKey();
        }

        return (new MailMessage)
            ->subject('Trip Request Cancelled - ' . $this->tripRequest->request_number)
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A trip request has been cancelled.')
            ->line('Request Number: ' . $this->tripRequest->request_number)
            ->line('Purpose: ' . ($this->tripRequest->purpose ?? 'N/A'))
            ->line('Destination: ' . ($this->tripRequest->destination ?? 'N/A'))
            ->line('Trip Date: ' . $tripDate)
            ->line('Trip Time: ' . $tripTime)
            ->line('Cancellation Reason: ' . ($this->tripRequest->cancellation_reason ?? 'N/A'))
            ->line('Cancelled By: ' . ($this->cancelledBy->name ?? 'System'))
            ->action('View Trip', route('trips.show', $routeKey))
            ->line('If you have questions, please contact your fleet manager.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'trip_request_uuid' => $this->tripRequest->uuid ?? null,
            'request_number' => $this->tripRequest->request_number,
            'purpose' => $this->tripRequest->purpose,
            'destination' => $this->tripRequest->destination,
            'trip_date' => $this->tripRequest->trip_date?->toDateString(),
            'trip_time' => $this->tripRequest->trip_time,
            'cancelled_by' => $this->cancelledBy->name ?? null,
            'cancellation_reason' => $this->tripRequest->cancellation_reason,
            'status' => 'cancelled',
        ];
    }
}
