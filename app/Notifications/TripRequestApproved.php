<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueueReliability;
use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;
    use QueueReliability;
    use SkipsInvalidMailRecipients;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private int $tripRequestId,
        private string $requestNumber,
        private string $status,
        private string $purpose,
        private string $destination,
        private ?string $approvedAt,
    )
    {
    }

    public static function fromTripRequest(\App\Models\TripRequest $tripRequest): self
    {
        return (new self(
            tripRequestId: (int) $tripRequest->id,
            requestNumber: (string) $tripRequest->request_number,
            status: (string) $tripRequest->status,
            purpose: (string) ($tripRequest->purpose ?? ''),
            destination: (string) ($tripRequest->destination ?? ''),
            approvedAt: $tripRequest->approved_at?->toDateTimeString(),
        ))
            ->onConnection('database')
            ->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return $this->shouldSendMailTo($notifiable)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Trip Approved '.$this->requestNumber)
            ->line('Your trip request has been approved.')
            ->line('Purpose: '.$this->purpose)
            ->line('Destination: '.$this->destination)
            ->action('View Trip Request', route('trips.show', $this->tripRequestId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trip_request_id' => $this->tripRequestId,
            'request_number' => $this->requestNumber,
            'status' => $this->status,
            'approved_at' => $this->approvedAt,
        ];
    }
}
