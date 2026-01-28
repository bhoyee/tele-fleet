<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TripAssignmentConflict extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TripRequest $tripRequest,
        public readonly string $message,
        public readonly bool $autoUnassigned = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequest->id,
            'request_number' => $this->tripRequest->request_number,
            'message' => $this->message,
            'auto_unassigned' => $this->autoUnassigned,
        ];
    }
}
