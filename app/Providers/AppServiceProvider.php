<?php

namespace App\Providers;

use App\Models\Driver;
use App\Models\IncidentReport;
use App\Models\AppSetting;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Throwable;

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

        // Make branding available everywhere (web/queue/console) via config().
        // This ensures emails/reports use the saved app name/logo even outside Blade view composers.
        try {
            $brandName = AppSetting::getValue('app_name', config('app.name', 'Tele-Fleet'));
            $orgName = AppSetting::getValue('org_name');
            $orgAddress = AppSetting::getValue('org_address');
            $logoPath = AppSetting::getValue('app_logo_path');

            $logoUrl = null;
            $logoFile = null;

            if (is_string($logoPath) && $logoPath !== '') {
                $normalized = str_replace('\\', '/', $logoPath);
                if (str_starts_with($normalized, 'branding/')) {
                    $publicFile = public_path($normalized);
                    if (File::exists($publicFile)) {
                        $logoUrl = asset($normalized);
                        $logoFile = $publicFile;
                    }
                }

                if (! $logoUrl) {
                    $logoUrl = url(Storage::disk('public')->url($normalized));
                }
            }

            config([
                'app.name' => $brandName,
                'mail.from.name' => $brandName,
                'app.org_name' => $orgName,
                'app.org_address' => $orgAddress,
                'app.brand_logo_url' => $logoUrl,
                'app.brand_logo_file' => $logoFile,
            ]);
        } catch (Throwable $exception) {
            // Never break boot; fallback to .env config.
        }

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

        View::composer('*', function ($view): void {
            $brandName = config('app.name', 'Tele-Fleet');
            $logoUrl = null;
            $orgName = null;
            $orgAddress = null;

            try {
                $brandName = config('app.name', $brandName);
                $orgName = config('app.org_name');
                $orgAddress = config('app.org_address');
                $logoUrl = config('app.brand_logo_url');
                $logoPath = AppSetting::getValue('app_logo_path');

                if (is_string($logoPath) && $logoPath !== '') {
                    $normalized = str_replace('\\', '/', $logoPath);
                    if (str_starts_with($normalized, 'branding/')) {
                        $publicFile = public_path($normalized);
                        if (File::exists($publicFile)) {
                            $logoUrl = asset($normalized);
                        }
                    }
                    if (! $logoUrl) {
                        $logoUrl = url(Storage::disk('public')->url($normalized));
                    }
                }
            } catch (Throwable $exception) {
                // Branding settings should never break page rendering (e.g. during install / db outage).
                $brandName = config('app.name', 'Tele-Fleet');
                $logoUrl = null;
                $orgName = null;
                $orgAddress = null;
            }

            $view->with('appBrandName', $brandName);
            $view->with('appLogoUrl', $logoUrl);
            $view->with('appOrgName', $orgName);
            $view->with('appOrgAddress', $orgAddress);
        });
    }
}
