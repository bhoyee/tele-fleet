<?php

use App\Models\Branch;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows fleet manager to create, update, and archive drivers', function () {
    $manager = User::factory()->create([
        'role' => User::ROLE_FLEET_MANAGER,
        'status' => User::STATUS_ACTIVE,
    ]);

    $branch = Branch::create([
        'name' => 'Driver Hub',
        'code' => 'DH-400',
        'city' => 'Port Harcourt',
        'state' => 'Rivers',
    ]);

    $createResponse = $this->actingAs($manager)->post(route('drivers.store'), [
        'full_name' => 'Samuel Driver',
        'license_number' => 'LIC-TEST-101',
        'license_expiry' => now()->addYear()->toDateString(),
        'phone' => '+234 800 000 2222',
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);

    $createResponse->assertRedirect(route('drivers.index'));

    $driver = Driver::where('license_number', 'LIC-TEST-101')->firstOrFail();

    $updateResponse = $this->actingAs($manager)->put(route('drivers.update', $driver), [
        'full_name' => 'Samuel Driver Updated',
        'license_number' => 'LIC-TEST-101',
        'license_expiry' => now()->addYear()->toDateString(),
        'phone' => '+234 800 000 2222',
        'branch_id' => $branch->id,
        'status' => 'suspended',
    ]);

    $updateResponse->assertRedirect(route('drivers.index'));
    $this->assertDatabaseHas('drivers', [
        'id' => $driver->id,
        'status' => 'suspended',
    ]);

    $deleteResponse = $this->actingAs($manager)->delete(route('drivers.destroy', $driver));
    $deleteResponse->assertRedirect(route('drivers.index'));
    $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
});
