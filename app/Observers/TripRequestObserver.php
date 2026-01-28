<?php

namespace App\Observers;

use App\Events\DashboardUpdated;
use App\Models\TripRequest;

class TripRequestObserver
{
    public function saved(TripRequest $tripRequest): void
    {
        event(new DashboardUpdated($tripRequest->branch_id, 'trip'));
    }

    public function deleted(TripRequest $tripRequest): void
    {
        event(new DashboardUpdated($tripRequest->branch_id, 'trip'));
    }
}
