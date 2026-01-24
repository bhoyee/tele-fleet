<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows super admin to create, update, and delete branches', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
        'status' => User::STATUS_ACTIVE,
    ]);

    $createResponse = $this->actingAs($admin)->post(route('branches.store'), [
        'name' => 'Operations Hub',
        'code' => 'OP-200',
        'city' => 'Lagos',
        'state' => 'Lagos',
        'is_head_office' => true,
    ]);

    $createResponse->assertRedirect(route('branches.index'));

    $branch = Branch::where('code', 'OP-200')->firstOrFail();

    $updateResponse = $this->actingAs($admin)->put(route('branches.update', $branch), [
        'name' => 'Operations Hub Updated',
        'code' => 'OP-200',
        'city' => 'Lagos',
        'state' => 'Lagos',
        'is_head_office' => true,
    ]);

    $updateResponse->assertRedirect(route('branches.index'));
    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
        'name' => 'Operations Hub Updated',
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('branches.destroy', $branch));
    $deleteResponse->assertRedirect(route('branches.index'));
    $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
});
