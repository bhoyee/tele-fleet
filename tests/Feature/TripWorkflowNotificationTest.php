<?php

use App\Models\Branch;
use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TripRequestApproved;
use App\Notifications\TripRequestAssigned;
use App\Notifications\TripRequestCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends notifications when a trip is created', function () {
    Notification::fake();

    $branch = Branch::create([
        'name' => 'Central',
        'code' => 'CTR-01',
        'city' => 'Lagos',
        'state' => 'Lagos',
    ]);

    $requester = User::factory()->create([
        'role' => User::ROLE_BRANCH_ADMIN,
        'branch_id' => $branch->id,
        'status' => User::STATUS_ACTIVE,
    ]);

    $fleetManager = User::factory()->create([
        'role' => User::ROLE_FLEET_MANAGER,
        'status' => User::STATUS_ACTIVE,
    ]);

    $branchHead = User::factory()->create([
        'role' => User::ROLE_BRANCH_HEAD,
        'branch_id' => $branch->id,
        'status' => User::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($requester)->post(route('trips.store'), [
        'purpose' => 'Client visit',
        'destination' => 'Victoria Island',
        'trip_date' => now()->addDay()->toDateString(),
        'number_of_passengers' => 2,
    ]);

    $response->assertRedirect();
    $trip = TripRequest::firstOrFail();

    Notification::assertSentTo([$fleetManager, $branchHead], TripRequestCreated::class, function ($notification) use ($trip) {
        return $notification->tripRequest->is($trip);
    });
});

it('approves, assigns, and completes a trip with notifications and status updates', function () {
    Notification::fake();
    Http::fake();

    config([
        'services.termii.key' => 'test-key',
        'services.termii.sender_id' => 'TeleFleet',
        'services.termii.base_url' => 'https://api.ng.termii.com',
    ]);

    $branch = Branch::create([
        'name' => 'Central',
        'code' => 'CTR-02',
        'city' => 'Lagos',
        'state' => 'Lagos',
    ]);

    $requester = User::factory()->create([
        'role' => User::ROLE_BRANCH_ADMIN,
        'branch_id' => $branch->id,
        'status' => User::STATUS_ACTIVE,
    ]);

    $fleetManager = User::factory()->create([
        'role' => User::ROLE_FLEET_MANAGER,
        'status' => User::STATUS_ACTIVE,
    ]);

    $branchHead = User::factory()->create([
        'role' => User::ROLE_BRANCH_HEAD,
        'branch_id' => $branch->id,
        'status' => User::STATUS_ACTIVE,
    ]);

    $vehicle = Vehicle::create([
        'registration_number' => 'TF-5555',
        'branch_id' => $branch->id,
        'make' => 'Toyota',
        'model' => 'Hiace',
        'year' => 2021,
        'fuel_type' => 'diesel',
        'current_mileage' => 1000,
        'status' => 'available',
    ]);

    $driver = Driver::create([
        'full_name' => 'Amaka Driver',
        'license_number' => 'LIC-5555',
        'license_expiry' => now()->addYear()->toDateString(),
        'phone' => '+2348000005555',
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);

    $this->actingAs($requester)->post(route('trips.store'), [
        'purpose' => 'Dispatch',
        'destination' => 'Apapa',
        'trip_date' => now()->addDay()->toDateString(),
    ]);

    $trip = TripRequest::firstOrFail();

    $approveResponse = $this->actingAs($fleetManager)->patch(route('trips.approve', $trip));
    $approveResponse->assertRedirect(route('trips.show', $trip));
    $trip->refresh();
    expect($trip->status)->toBe('approved');

    Notification::assertSentTo([$requester, $fleetManager, $branchHead], TripRequestApproved::class);

    $assignResponse = $this->actingAs($fleetManager)->patch(route('trips.assign.store', $trip), [
        'assigned_vehicle_id' => $vehicle->id,
        'assigned_driver_id' => $driver->id,
    ]);

    $assignResponse->assertRedirect(route('trips.show', $trip));
    $trip->refresh();
    $vehicle->refresh();
    $driver->refresh();

    expect($trip->status)->toBe('assigned');
    expect($vehicle->status)->toBe('in_use');
    expect($driver->status)->toBe('inactive');

    Notification::assertSentTo([$requester, $fleetManager, $branchHead], TripRequestAssigned::class);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/sms/send'));

    $logResponse = $this->actingAs($fleetManager)->post(route('trips.logbook.store', $trip), [
        'start_mileage' => 1000,
        'end_mileage' => 1120,
        'driver_name' => $driver->full_name,
        'driver_license_number' => $driver->license_number,
        'log_date' => now()->toDateString(),
    ]);

    $logResponse->assertRedirect(route('trips.show', $trip));
    $trip->refresh();
    $vehicle->refresh();
    $driver->refresh();

    expect($trip->status)->toBe('completed');
    expect($vehicle->status)->toBe('available');
    expect($driver->status)->toBe('active');
});
