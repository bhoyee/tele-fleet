<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserManualController;
use App\Http\Controllers\Admin\MaintenanceSettingsController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\ChatManagementController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Fleet\DriverController;
use App\Http\Controllers\Fleet\IncidentReportController;
use App\Http\Controllers\Fleet\MaintenanceController;
use App\Http\Controllers\Fleet\TripRequestController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $totalVehicles = \App\Models\Vehicle::count();
    $maintenanceVehicles = \App\Models\Vehicle::where('status', 'maintenance')->count();
    $totalDrivers = \App\Models\Driver::count();
    $totalBranches = \App\Models\Branch::count();
    $activeTrips = \App\Models\TripRequest::whereNotNull('assigned_vehicle_id')
        ->whereIn('status', ['approved', 'assigned'])
        ->where(function ($query): void {
            $query->whereNull('is_completed')->orWhere('is_completed', false);
        })
        ->whereDate('trip_date', '<=', now()->toDateString())
        ->count();
    $availableVehicles = max(0, $totalVehicles - $maintenanceVehicles - $activeTrips);
    $completedToday = \App\Models\TripRequest::where('status', 'completed')
        ->whereDate('trip_date', now()->toDateString())
        ->count();
    $utilization = $totalVehicles > 0
        ? (int) round((max(0, $totalVehicles - $availableVehicles) / $totalVehicles) * 100)
        : 0;

    return view('welcome', [
        'landingMetrics' => [
            'active_trips' => $activeTrips,
            'available_vehicles' => $availableVehicles,
            'completed_today' => $completedToday,
            'utilization' => $utilization,
            'total_vehicles' => $totalVehicles,
            'total_drivers' => $totalDrivers,
            'total_branches' => $totalBranches,
        ],
    ]);
});

Route::get('/landing-metrics', function () {
    $totalVehicles = \App\Models\Vehicle::count();
    $maintenanceVehicles = \App\Models\Vehicle::where('status', 'maintenance')->count();
    $totalDrivers = \App\Models\Driver::count();
    $totalBranches = \App\Models\Branch::count();
    $activeTrips = \App\Models\TripRequest::whereNotNull('assigned_vehicle_id')
        ->whereIn('status', ['approved', 'assigned'])
        ->where(function ($query): void {
            $query->whereNull('is_completed')->orWhere('is_completed', false);
        })
        ->whereDate('trip_date', '<=', now()->toDateString())
        ->count();
    $availableVehicles = max(0, $totalVehicles - $maintenanceVehicles - $activeTrips);
    $completedToday = \App\Models\TripRequest::where('status', 'completed')
        ->whereDate('trip_date', now()->toDateString())
        ->count();
    $utilization = $totalVehicles > 0
        ? (int) round((max(0, $totalVehicles - $availableVehicles) / $totalVehicles) * 100)
        : 0;

    return response()->json([
        'active_trips' => $activeTrips,
        'available_vehicles' => $availableVehicles,
        'completed_today' => $completedToday,
        'utilization' => $utilization,
        'total_vehicles' => $totalVehicles,
        'total_drivers' => $totalDrivers,
        'total_branches' => $totalBranches,
    ]);
})->name('landing.metrics');

Route::post('/admin/sms/test', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'phone' => ['required', 'string'],
        'message' => ['required', 'string', 'max:160'],
    ]);

    $response = app(\App\Services\SmsService::class)->send($request->string('phone'), $request->string('message'));

    return back()->with('sms_response', $response);
})->middleware(['auth', 'role:super_admin'])->name('sms.test');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.metrics');
Route::get('/dashboard/calendar', [DashboardController::class, 'calendar'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.calendar');
Route::get('/dashboard/trip-status', [DashboardController::class, 'tripStatus'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.trip-status');
Route::get('/dashboard/upcoming-trips', [DashboardController::class, 'upcomingTrips'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.upcoming-trips');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    Route::delete('/notifications/cleanup', [NotificationController::class, 'cleanupDuplicates'])->name('notifications.cleanup');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::get('maintenance-settings', [MaintenanceSettingsController::class, 'edit'])->name('maintenance-settings.edit');
    Route::patch('maintenance-settings', [MaintenanceSettingsController::class, 'update'])->name('maintenance-settings.update');
    Route::get('chats', [ChatManagementController::class, 'index'])->name('chats.index');
    Route::get('chats/{conversation}', [ChatManagementController::class, 'show'])->withTrashed()->name('chats.show');
    Route::patch('chats/{conversation}/close', [ChatManagementController::class, 'close'])->withTrashed()->name('chats.close');
    Route::delete('chats/{conversation}', [ChatManagementController::class, 'destroy'])->withTrashed()->name('chats.destroy');
    Route::get('user-manual', UserManualController::class)->name('user-manual');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('branches', BranchController::class);
    Route::get('admin/health', [HealthController::class, 'index'])->name('admin.health');
    Route::get('admin/health/data', [HealthController::class, 'data'])->name('admin.health.data');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::resource('vehicles', VehicleController::class);
    Route::get('vehicles/data', [VehicleController::class, 'indexData'])->name('vehicles.data');
    Route::resource('drivers', DriverController::class);
    Route::get('drivers/data', [DriverController::class, 'indexData'])->name('drivers.data');
    Route::resource('maintenances', MaintenanceController::class);
    Route::get('maintenances/data', [MaintenanceController::class, 'indexData'])->name('maintenances.data');
    Route::get('maintenances/export/csv', [MaintenanceController::class, 'exportCsv'])->name('maintenances.export.csv');
    Route::get('maintenances/export/pdf', [MaintenanceController::class, 'exportPdf'])->name('maintenances.export.pdf');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::patch('vehicles/{vehicle}/restore', [VehicleController::class, 'restore'])->name('vehicles.restore');
    Route::delete('vehicles/{vehicle}/force', [VehicleController::class, 'forceDelete'])->name('vehicles.force');
    Route::patch('drivers/{driver}/restore', [DriverController::class, 'restore'])->name('drivers.restore');
    Route::delete('drivers/{driver}/force', [DriverController::class, 'forceDelete'])->name('drivers.force');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('incidents/create', [IncidentReportController::class, 'create'])->name('incidents.create');
    Route::post('incidents', [IncidentReportController::class, 'store'])->name('incidents.store');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('incidents', [IncidentReportController::class, 'index'])->name('incidents.index');
    Route::get('incidents/data', [IncidentReportController::class, 'indexData'])->name('incidents.data');
    Route::get('incidents/{incident}', [IncidentReportController::class, 'show'])->name('incidents.show');
    Route::get('incidents/{incident}/edit', [IncidentReportController::class, 'edit'])->name('incidents.edit');
    Route::patch('incidents/{incident}', [IncidentReportController::class, 'update'])->name('incidents.update');
    Route::patch('incidents/{incident}/cancel', [IncidentReportController::class, 'cancel'])->name('incidents.cancel');
    Route::get('incidents/{incident}/attachments/{filename}/preview', [IncidentReportController::class, 'previewAttachment'])->name('incidents.attachments.preview');
    Route::get('incidents/{incident}/attachments/{filename}', [IncidentReportController::class, 'downloadAttachment'])->name('incidents.attachments.download');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::patch('incidents/{incident}/status', [IncidentReportController::class, 'updateStatus'])->name('incidents.status');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::delete('incidents/{incident}', [IncidentReportController::class, 'destroy'])->name('incidents.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::patch('incidents/{incident}/restore', [IncidentReportController::class, 'restore'])->name('incidents.restore');
    Route::delete('incidents/{incident}/force', [IncidentReportController::class, 'forceDelete'])->name('incidents.force');
    Route::get('incidents/export/csv', [IncidentReportController::class, 'exportCsv'])->name('incidents.export.csv');
    Route::get('incidents/export/pdf', [IncidentReportController::class, 'exportPdf'])->name('incidents.export.pdf');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('trips/create', [TripRequestController::class, 'create'])->name('trips.create');
    Route::post('trips', [TripRequestController::class, 'store'])->name('trips.store');
    Route::get('trips/my-requests', [TripRequestController::class, 'myRequests'])->name('trips.my');
    Route::get('trips/data', [TripRequestController::class, 'indexData'])->name('trips.data');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('trips/{tripRequest}/edit', [TripRequestController::class, 'edit'])->name('trips.edit');
    Route::patch('trips/{tripRequest}', [TripRequestController::class, 'update'])->name('trips.update');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('trips', [TripRequestController::class, 'index'])->name('trips.index');
    Route::get('trips/{tripRequest}', [TripRequestController::class, 'show'])->name('trips.show');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('reports/my-requests', [ReportController::class, 'myRequests'])->name('reports.my-requests');
    Route::get('reports/my-requests/export/excel', [ReportController::class, 'exportMyRequestsExcel'])->name('reports.my-requests.excel');
    Route::get('reports/my-requests/export/pdf', [ReportController::class, 'exportMyRequestsPdf'])->name('reports.my-requests.pdf');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::get('reports/fleet', [ReportController::class, 'fleetReport'])->name('reports.fleet');
    Route::get('reports/fleet/export/csv', [ReportController::class, 'exportFleetReportCsv'])->name('reports.fleet.csv');
    Route::get('reports/fleet/export/pdf', [ReportController::class, 'exportFleetReportPdf'])->name('reports.fleet.pdf');
    Route::get('reports/custom', [ReportController::class, 'customReport'])->name('reports.custom');
    Route::get('reports/custom/export/csv', [ReportController::class, 'exportCustomReportCsv'])->name('reports.custom.csv');
    Route::get('reports/custom/export/pdf', [ReportController::class, 'exportCustomReportPdf'])->name('reports.custom.pdf');
});

Route::middleware(['auth', 'role:branch_admin,branch_head'])->group(function () {
    Route::get('reports/branch', [ReportController::class, 'branchReport'])->name('reports.branch');
    Route::get('reports/branch/export/excel', [ReportController::class, 'exportBranchExcel'])->name('reports.branch.excel');
    Route::get('reports/branch/export/pdf', [ReportController::class, 'exportBranchPdf'])->name('reports.branch.pdf');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::get('logbooks', [TripRequestController::class, 'logbookIndex'])->name('logbooks.index');
    Route::get('logbooks/manage', [TripRequestController::class, 'manageLogbooks'])->name('logbooks.manage');
    Route::get('logbooks/{tripLog}', [TripRequestController::class, 'showLogbook'])->name('logbooks.show');
    Route::delete('logbooks/{tripLog}', [TripRequestController::class, 'archiveLogbook'])->name('logbooks.archive');
    Route::patch('trips/{tripRequest}/approve', [TripRequestController::class, 'approve'])->name('trips.approve');
    Route::patch('trips/{tripRequest}/reject', [TripRequestController::class, 'reject'])->name('trips.reject');
    Route::patch('trips/{tripRequest}/cancel', [TripRequestController::class, 'cancel'])->name('trips.cancel');
    Route::get('trips/{tripRequest}/assign', [TripRequestController::class, 'assignmentForm'])->name('trips.assign');
    Route::patch('trips/{tripRequest}/assign', [TripRequestController::class, 'assign'])->name('trips.assign.store');
    Route::get('trips/{tripRequest}/logbook', [TripRequestController::class, 'logbook'])->name('trips.logbook');
    Route::post('trips/{tripRequest}/logbook', [TripRequestController::class, 'storeLogbook'])->name('trips.logbook.store');
    Route::get('trips/{tripRequest}/logbook/edit', [TripRequestController::class, 'editLogbook'])->name('trips.logbook.edit');
    Route::patch('trips/{tripRequest}/logbook', [TripRequestController::class, 'updateLogbook'])->name('trips.logbook.update');
    Route::delete('trips/{tripRequest}/logbook', [TripRequestController::class, 'destroyLogbook'])->name('trips.logbook.destroy');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::delete('trips/{tripRequest}', [TripRequestController::class, 'destroy'])->name('trips.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::patch('trips/{tripRequest}/restore', [TripRequestController::class, 'restore'])->name('trips.restore');
    Route::delete('trips/{tripRequest}/force', [TripRequestController::class, 'forceDelete'])->name('trips.force');
    Route::patch('logbooks/{tripLog}/restore', [TripRequestController::class, 'restoreLogbook'])->name('logbooks.restore');
    Route::delete('logbooks/{tripLog}/force', [TripRequestController::class, 'forceDeleteLogbook'])->name('logbooks.force');
    Route::get('system/backups', [\App\Http\Controllers\Admin\SystemToolsController::class, 'backups'])->name('system.backups');
    Route::post('system/backups/run', [\App\Http\Controllers\Admin\SystemToolsController::class, 'runBackup'])->name('system.backups.run');
    Route::get('system/backups/download/{filename}', [\App\Http\Controllers\Admin\SystemToolsController::class, 'downloadBackup'])->name('system.backups.download');
    Route::delete('system/backups/{filename}', [\App\Http\Controllers\Admin\SystemToolsController::class, 'deleteBackup'])->name('system.backups.delete');
    Route::get('system/logs', [\App\Http\Controllers\Admin\SystemToolsController::class, 'logs'])->name('system.logs');
    Route::get('system/logs/download/{filename}', [\App\Http\Controllers\Admin\SystemToolsController::class, 'downloadLog'])->name('system.logs.download');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('chat/widget/conversations', [ChatController::class, 'widgetConversations'])->name('chat.widget.conversations');
    Route::get('chat/widget/conversations/{conversation}', [ChatController::class, 'widgetConversation'])->name('chat.widget.conversation');
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.store');
    Route::patch('chat/{conversation}/accept', [ChatController::class, 'accept'])->name('chat.accept');
    Route::patch('chat/{conversation}/decline', [ChatController::class, 'decline'])->name('chat.decline');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::patch('chat/{conversation}/close', [ChatController::class, 'close'])->name('chat.close');
    Route::delete('chat/{conversation}/history', [ChatController::class, 'softDeleteHistory'])->name('chat.history.delete');
});

Route::middleware(['auth', 'role:branch_admin,branch_head'])->group(function () {
    Route::post('chat/support', [ChatController::class, 'storeSupport'])->name('chat.support');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::post('chat/direct', [ChatController::class, 'storeDirect'])->name('chat.direct');
});

require __DIR__.'/auth.php';
