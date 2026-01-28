<?php

namespace App\Observers;

use App\Events\DashboardUpdated;
use App\Models\IncidentReport;

class IncidentReportObserver
{
    public function saved(IncidentReport $incidentReport): void
    {
        event(new DashboardUpdated($incidentReport->branch_id, 'incident'));
    }

    public function deleted(IncidentReport $incidentReport): void
    {
        event(new DashboardUpdated($incidentReport->branch_id, 'incident'));
    }
}
