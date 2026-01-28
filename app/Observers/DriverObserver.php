<?php

namespace App\Observers;

use App\Events\DashboardUpdated;
use App\Events\DriverChanged;
use App\Models\Driver;

class DriverObserver
{
    public function saved(Driver $driver): void
    {
        event(new DashboardUpdated($driver->branch_id, 'driver'));
        event(new DriverChanged($driver->id, 'saved'));
    }

    public function deleted(Driver $driver): void
    {
        event(new DashboardUpdated($driver->branch_id, 'driver'));
        event(new DriverChanged($driver->id, 'deleted'));
    }
}
