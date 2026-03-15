<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates the joya importada configuration through migrations', function () {
    $this->assertDatabaseHas('cogs', [
        'name' => 'Joya importada',
        'value' => 1350.00,
    ]);
});

it('lists the joya importada configuration in the cogs endpoint', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/cogs')
        ->assertOk()
        ->assertJsonFragment([
            'name' => 'Joya importada',
            'value' => 1350,
            'description' => 'Precio de joya importada',
        ]);
});
