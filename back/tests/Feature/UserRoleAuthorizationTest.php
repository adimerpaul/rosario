<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('prevents a non admin user from changing another user role', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $user = User::factory()->create(['role' => 'Vendedor']);

    $this->putJson('/api/users/'.$user->id, [
        'name' => $user->name,
        'username' => $user->username,
        'role' => 'Administrador',
    ])->assertForbidden();

    expect($user->fresh()->role)->toBe('Vendedor');
});

it('allows an admin user to change another user role', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $user = User::factory()->create(['role' => 'Vendedor']);

    $this->putJson('/api/users/'.$user->id, [
        'name' => $user->name,
        'username' => $user->username,
        'role' => 'Administrador',
    ])->assertOk()
        ->assertJsonPath('role', 'Administrador');

    expect($user->fresh()->role)->toBe('Administrador');
});
