<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentReportChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $incidentId,
        public ?int $branchId,
        public ?int $reporterId,
        public string $action = 'updated'
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->branchId) {
            $channels[] = new PrivateChannel('incidents.branch.' . $this->branchId);
        }

        if ($this->reporterId) {
            $channels[] = new PrivateChannel('incidents.user.' . $this->reporterId);
        }

        $channels[] = new PrivateChannel('incidents.all');

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'incident.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id' => $this->incidentId,
            'branch_id' => $this->branchId,
            'action' => $this->action,
        ];
    }
}
