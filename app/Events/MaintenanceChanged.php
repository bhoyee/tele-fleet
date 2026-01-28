<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $maintenanceId,
        public string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('maintenances.all')];
    }

    public function broadcastAs(): string
    {
        return 'maintenance.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'maintenance_id' => $this->maintenanceId,
            'action' => $this->action,
        ];
    }
}
