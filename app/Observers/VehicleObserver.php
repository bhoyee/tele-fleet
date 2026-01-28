<?php

namespace App\Observers;

use App\Events\DashboardUpdated;
use App\Events\VehicleChanged;
use App\Models\Vehicle;

class VehicleObserver
{
    public function saved(Vehicle $vehicle): void
    {
        event(new DashboardUpdated($vehicle->branch_id, 'vehicle'));
        event(new VehicleChanged($vehicle->id, 'saved'));
    }

    public function deleted(Vehicle $vehicle): void
    {
        event(new DashboardUpdated($vehicle->branch_id, 'vehicle'));
        event(new VehicleChanged($vehicle->id, 'deleted'));
    }
}
