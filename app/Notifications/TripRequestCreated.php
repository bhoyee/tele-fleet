<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $tripRequestId,
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
        return new self(
            tripRequestId: (int) $tripRequest->id,
            requestNumber: (string) $tripRequest->request_number,
            status: (string) $tripRequest->status,
            purpose: (string) $tripRequest->purpose,
            destination: (string) $tripRequest->destination,
            tripDate: $tripRequest->trip_date?->toDateString(),
        );
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Trip Request '.$this->requestNumber)
            ->line('A new trip request has been submitted.')
            ->line('Purpose: '.$this->purpose)
            ->line('Destination: '.$this->destination)
            ->action('View Trip Request', route('trips.show', $this->tripRequestId))
            ->line('Please review and proceed with approval.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequestId,
            'request_number' => $this->requestNumber,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'destination' => $this->destination,
            'trip_date' => $this->tripDate,
        ];
    }
}
