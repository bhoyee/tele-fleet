<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Notifications\Notification;

class TripRequestCreatedInApp extends Notification
{
    public function __construct(
        private int $tripRequestId,
        private string $requestNumber,
        private string $status,
        private string $purpose,
        private string $destination,
        private ?string $tripDate,
    ) {
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
        return ['database'];
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

