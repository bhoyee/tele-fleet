<?php

use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows fleet manager to create, update, and archive vehicles', function () {
    $manager = User::factory()->create([
        'role' => User::ROLE_FLEET_MANAGER,
        'status' => User::STATUS_ACTIVE,
    ]);

    $branch = Branch::create([
        'name' => 'Fleet Hub',
        'code' => 'FH-300',
        'city' => 'Abuja',
        'state' => 'FCT',
    ]);

    $createResponse = $this->actingAs($manager)->post(route('vehicles.store'), [
        'registration_number' => 'TF-9001',
        'branch_id' => $branch->id,
        'make' => 'Toyota',
        'model' => 'Hilux',
        'year' => 2021,
        'fuel_type' => 'diesel',
        'current_mileage' => 12000,
        'status' => 'available',
    ]);

    $createResponse->assertRedirect(route('vehicles.index'));

    $vehicle = Vehicle::where('registration_number', 'TF-9001')->firstOrFail();

    $updateResponse = $this->actingAs($manager)->put(route('vehicles.update', $vehicle), [
        'registration_number' => 'TF-9001',
        'branch_id' => $branch->id,
        'make' => 'Toyota',
        'model' => 'Hilux',
        'year' => 2021,
        'fuel_type' => 'diesel',
        'current_mileage' => 13000,
        'status' => 'maintenance',
    ]);

    $updateResponse->assertRedirect(route('vehicles.index'));
    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'status' => 'maintenance',
    ]);

    $deleteResponse = $this->actingAs($manager)->delete(route('vehicles.destroy', $vehicle));
    $deleteResponse->assertRedirect(route('vehicles.index'));
    $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
});
