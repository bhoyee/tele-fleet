<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripRequestChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $tripId,
        public ?int $branchId,
        public ?int $requesterId,
        public string $action = 'updated'
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->branchId) {
            $channels[] = new PrivateChannel('trips.branch.' . $this->branchId);
        }

        if ($this->requesterId) {
            $channels[] = new PrivateChannel('trips.user.' . $this->requesterId);
        }

        $channels[] = new PrivateChannel('trips.all');

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'trip.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->tripId,
            'branch_id' => $this->branchId,
            'action' => $this->action,
        ];
    }
}
