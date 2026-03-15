<?php

use App\Models\Joya;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates fake joyas from seed data', function () {
    $this->assertDatabaseCount('joyas', 5);
});

it('allows an admin to create a joya', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $this->postJson('/api/joyas', [
        'tipo' => 'Importada',
        'peso' => 5,
        'linea' => 'Mama',
        'estuche' => 'Estuche premium',
        'nombre' => 'Anillo prueba',
        'monto_bs' => 5400,
    ])->assertCreated()
        ->assertJsonFragment([
            'tipo' => 'Importada',
            'linea' => 'Mama',
            'nombre' => 'ANILLO PRUEBA',
            'monto_bs' => 5400,
        ]);
});

it('forbids a vendedor from accessing joyas crud', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $this->getJson('/api/joyas')->assertForbidden();
    $this->postJson('/api/joyas', [
        'tipo' => 'Plata',
        'peso' => 1,
        'linea' => 'Roger',
        'estuche' => 'Estuche',
        'nombre' => 'Prueba',
        'monto_bs' => 100,
    ])->assertForbidden();
});

it('allows an admin to update and delete a joya', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $joya = Joya::create([
        'tipo' => 'Importada',
        'peso' => 4,
        'linea' => 'Papa',
        'estuche' => 'ESTUCHE BASE',
        'nombre' => 'ANILLO BASE',
        'imagen' => 'default.png',
        'monto_bs' => 500,
    ]);

    $this->putJson('/api/joyas/'.$joya->id, [
        'tipo' => 'Plata',
        'peso' => 9.5,
        'linea' => 'Andreina',
        'estuche' => 'Estuche actualizado',
        'nombre' => 'Collar editado',
        'monto_bs' => 999,
    ])->assertOk()
        ->assertJsonFragment([
            'tipo' => 'Plata',
            'linea' => 'Andreina',
            'nombre' => 'COLLAR EDITADO',
        ]);

    $this->deleteJson('/api/joyas/'.$joya->id)
        ->assertOk();

    $this->assertSoftDeleted('joyas', ['id' => $joya->id]);
});
