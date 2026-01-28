<?php

namespace App\Observers;

use App\Events\MaintenanceChanged;
use App\Models\VehicleMaintenance;

class VehicleMaintenanceObserver
{
    public function saved(VehicleMaintenance $maintenance): void
    {
        event(new MaintenanceChanged($maintenance->id, 'saved'));
    }

    public function deleted(VehicleMaintenance $maintenance): void
    {
        event(new MaintenanceChanged($maintenance->id, 'deleted'));
    }
}
