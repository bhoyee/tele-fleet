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
use App\Models\LoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSent;

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

        Event::listen(MessageSent::class, function (): void {
            Cache::put('telefleet.mail_last_sent_at', now()->format('M d, Y H:i:s'), now()->addDays(7));
        });

        Event::listen(Login::class, function (Login $event): void {
            $request = request();
            LoginHistory::create([
                'user_id' => $event->user?->id,
                'guard' => $event->guard,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'logged_in_at' => now(),
            ]);
        });
    }
}
