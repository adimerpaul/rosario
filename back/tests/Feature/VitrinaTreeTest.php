<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('shows vitrinas tree to administrador and vendedor', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $this->getJson('/api/vitrinas')
        ->assertOk()
        ->assertJsonFragment(['nombre' => 'V1'])
        ->assertJsonFragment(['codigo' => '1.1']);

    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $this->getJson('/api/vitrinas')
        ->assertOk()
        ->assertJsonFragment(['nombre' => 'V3']);
});

it('allows only admin to manage vitrinas structure', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $this->postJson('/api/vitrinas', [
        'nombre' => 'V EXTRA',
        'orden' => 99,
    ])->assertForbidden();

    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $this->postJson('/api/vitrinas', [
        'nombre' => 'V EXTRA',
        'orden' => 99,
    ])->assertCreated();
});
