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
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

Route::middleware(['auth', 'role:branch_admin,branch_head'])->group(function () {
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

Route::middleware(['auth', 'role:branch_admin,branch_head'])->group(function () {
    Route::get('reports/branch', [ReportController::class, 'branchReport'])->name('reports.branch');
    Route::get('reports/branch/export/excel', [ReportController::class, 'exportBranchExcel'])->name('reports.branch.excel');
    Route::get('reports/branch/export/pdf', [ReportController::class, 'exportBranchPdf'])->name('reports.branch.pdf');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::get('logbooks', [TripRequestController::class, 'logbookIndex'])->name('logbooks.index');
    Route::patch('trips/{tripRequest}/approve', [TripRequestController::class, 'approve'])->name('trips.approve');
    Route::patch('trips/{tripRequest}/reject', [TripRequestController::class, 'reject'])->name('trips.reject');
    Route::patch('trips/{tripRequest}/cancel', [TripRequestController::class, 'cancel'])->name('trips.cancel');
    Route::get('trips/{tripRequest}/assign', [TripRequestController::class, 'assignmentForm'])->name('trips.assign');
    Route::patch('trips/{tripRequest}/assign', [TripRequestController::class, 'assign'])->name('trips.assign.store');
    Route::get('trips/{tripRequest}/logbook', [TripRequestController::class, 'logbook'])->name('trips.logbook');
    Route::post('trips/{tripRequest}/logbook', [TripRequestController::class, 'storeLogbook'])->name('trips.logbook.store');
    Route::get('trips/{tripRequest}/logbook/edit', [TripRequestController::class, 'editLogbook'])->name('trips.logbook.edit');
    Route::patch('trips/{tripRequest}/logbook', [TripRequestController::class, 'updateLogbook'])->name('trips.logbook.update');
});

Route::middleware(['auth', 'role:super_admin,branch_admin,branch_head'])->group(function () {
    Route::delete('trips/{tripRequest}', [TripRequestController::class, 'destroy'])->name('trips.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::delete('trips/{tripRequest}/logbook', [TripRequestController::class, 'destroyLogbook'])->name('trips.logbook.destroy');
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
});

Route::middleware(['auth', 'role:branch_admin,branch_head'])->group(function () {
    Route::post('chat/support', [ChatController::class, 'storeSupport'])->name('chat.support');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::post('chat/direct', [ChatController::class, 'storeDirect'])->name('chat.direct');
});

require __DIR__.'/auth.php';
