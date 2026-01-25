<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Fleet\DriverController;
use App\Http\Controllers\Fleet\IncidentReportController;
use App\Http\Controllers\Fleet\TripRequestController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('branches', BranchController::class)->except(['show']);
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::resource('drivers', DriverController::class)->except(['show']);
});

Route::middleware(['auth', 'role:branch_admin'])->group(function () {
    Route::get('incidents/create', [IncidentReportController::class, 'create'])->name('incidents.create');
    Route::post('incidents', [IncidentReportController::class, 'store'])->name('incidents.store');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('incidents', [IncidentReportController::class, 'index'])->name('incidents.index');
    Route::get('incidents/{incident}', [IncidentReportController::class, 'show'])->name('incidents.show');
    Route::get('incidents/{incident}/attachments/{filename}', [IncidentReportController::class, 'downloadAttachment'])->name('incidents.attachments.download');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::patch('incidents/{incident}/status', [IncidentReportController::class, 'updateStatus'])->name('incidents.status');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::delete('incidents/{incident}', [IncidentReportController::class, 'destroy'])->name('incidents.destroy');
    Route::get('incidents/export/csv', [IncidentReportController::class, 'exportCsv'])->name('incidents.export.csv');
    Route::get('incidents/export/pdf', [IncidentReportController::class, 'exportPdf'])->name('incidents.export.pdf');
});

Route::middleware(['auth', 'role:super_admin,branch_admin,branch_head'])->group(function () {
    Route::get('trips/create', [TripRequestController::class, 'create'])->name('trips.create');
    Route::post('trips', [TripRequestController::class, 'store'])->name('trips.store');
    Route::get('trips/my-requests', [TripRequestController::class, 'myRequests'])->name('trips.my');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
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
    Route::get('logbooks', [TripRequestController::class, 'logbookIndex'])->name('logbooks.index');
    Route::patch('trips/{tripRequest}/approve', [TripRequestController::class, 'approve'])->name('trips.approve');
    Route::patch('trips/{tripRequest}/reject', [TripRequestController::class, 'reject'])->name('trips.reject');
    Route::get('trips/{tripRequest}/assign', [TripRequestController::class, 'assignmentForm'])->name('trips.assign');
    Route::patch('trips/{tripRequest}/assign', [TripRequestController::class, 'assign'])->name('trips.assign.store');
    Route::get('trips/{tripRequest}/logbook', [TripRequestController::class, 'logbook'])->name('trips.logbook');
    Route::post('trips/{tripRequest}/logbook', [TripRequestController::class, 'storeLogbook'])->name('trips.logbook.store');
    Route::get('trips/{tripRequest}/logbook/edit', [TripRequestController::class, 'editLogbook'])->name('trips.logbook.edit');
    Route::patch('trips/{tripRequest}/logbook', [TripRequestController::class, 'updateLogbook'])->name('trips.logbook.update');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::delete('trips/{tripRequest}', [TripRequestController::class, 'destroy'])->name('trips.destroy');
    Route::delete('trips/{tripRequest}/logbook', [TripRequestController::class, 'destroyLogbook'])->name('trips.logbook.destroy');
});

require __DIR__.'/auth.php';
