<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $vehicleId,
        public string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('vehicles.all')];
    }

    public function broadcastAs(): string
    {
        return 'vehicle.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'vehicle_id' => $this->vehicleId,
            'action' => $this->action,
        ];
    }
}
