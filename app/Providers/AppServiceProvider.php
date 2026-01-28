<?php

namespace App\Providers;

use App\Models\Driver;
use App\Models\IncidentReport;
use App\Models\TripRequest;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Observers\DriverObserver;
use App\Observers\IncidentReportObserver;
use App\Observers\TripRequestObserver;
use App\Observers\VehicleMaintenanceObserver;
use App\Observers\VehicleObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        TripRequest::observe(TripRequestObserver::class);
        Vehicle::observe(VehicleObserver::class);
        Driver::observe(DriverObserver::class);
        IncidentReport::observe(IncidentReportObserver::class);
        VehicleMaintenance::observe(VehicleMaintenanceObserver::class);
    }
}
