<?php

namespace App\Notifications;

use App\Models\TripRequest;
use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestCreated extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $tripRequestId,
        private ?string $tripRequestUuid,
        private string $requestNumber,
        private string $status,
        private string $purpose,
        private string $destination,
        private ?string $tripDate,
    )
    {
    }

    public static function fromTripRequest(TripRequest $tripRequest): self
    {
        return (new self(
            tripRequestId: (int) $tripRequest->id,
            tripRequestUuid: is_string($tripRequest->uuid ?? null) ? $tripRequest->uuid : null,
            requestNumber: (string) $tripRequest->request_number,
            status: (string) $tripRequest->status,
            purpose: (string) $tripRequest->purpose,
            destination: (string) $tripRequest->destination,
            tripDate: $tripRequest->trip_date?->toDateString(),
        ))
            ->onConnection('database')
            ->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['mail']
            : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $routeKey = $this->tripRequestUuid ?: $this->tripRequestId;

        return (new MailMessage)
            ->subject('New Trip Request '.$this->requestNumber)
            ->line('A new trip request has been submitted.')
            ->line('Purpose: '.$this->purpose)
            ->line('Destination: '.$this->destination)
            ->action('View Trip Request', route('trips.show', $routeKey))
            ->line('Please review and proceed with approval.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequestId,
            'trip_request_uuid' => $this->tripRequestUuid,
            'request_number' => $this->requestNumber,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'destination' => $this->destination,
            'trip_date' => $this->tripDate,
        ];
    }
}
