<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Fleet\DriverController;
use App\Http\Controllers\Fleet\TripRequestController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
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

Route::middleware(['auth', 'role:super_admin,fleet_manager,branch_admin,branch_head'])->group(function () {
    Route::get('trips', [TripRequestController::class, 'index'])->name('trips.index');
    Route::get('trips/{tripRequest}', [TripRequestController::class, 'show'])->name('trips.show');
});

Route::middleware(['auth', 'role:super_admin,branch_admin,branch_head'])->group(function () {
    Route::get('trips/create', [TripRequestController::class, 'create'])->name('trips.create');
    Route::post('trips', [TripRequestController::class, 'store'])->name('trips.store');
});

Route::middleware(['auth', 'role:super_admin,fleet_manager'])->group(function () {
    Route::patch('trips/{tripRequest}/approve', [TripRequestController::class, 'approve'])->name('trips.approve');
    Route::patch('trips/{tripRequest}/reject', [TripRequestController::class, 'reject'])->name('trips.reject');
    Route::get('trips/{tripRequest}/assign', [TripRequestController::class, 'assignmentForm'])->name('trips.assign');
    Route::patch('trips/{tripRequest}/assign', [TripRequestController::class, 'assign'])->name('trips.assign.store');
    Route::get('trips/{tripRequest}/logbook', [TripRequestController::class, 'logbook'])->name('trips.logbook');
    Route::post('trips/{tripRequest}/logbook', [TripRequestController::class, 'storeLogbook'])->name('trips.logbook.store');
});

require __DIR__.'/auth.php';
