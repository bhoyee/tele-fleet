<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $driverId,
        public string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('drivers.all')];
    }

    public function broadcastAs(): string
    {
        return 'driver.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverId,
            'action' => $this->action,
        ];
    }
}
