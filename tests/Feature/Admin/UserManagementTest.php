<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows super admin to create a user with an auto generated password', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
        'status' => User::STATUS_ACTIVE,
    ]);

    $branch = Branch::create([
        'name' => 'Test Branch',
        'code' => 'TB-100',
        'city' => 'Test City',
        'state' => 'Test State',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Branch Admin',
        'email' => 'branch.admin@example.com',
        'phone' => '+234 800 000 1111',
        'role' => User::ROLE_BRANCH_ADMIN,
        'branch_id' => $branch->id,
        'status' => User::STATUS_ACTIVE,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('generated_password');

    $this->assertDatabaseHas('users', [
        'email' => 'branch.admin@example.com',
        'role' => User::ROLE_BRANCH_ADMIN,
    ]);
});
