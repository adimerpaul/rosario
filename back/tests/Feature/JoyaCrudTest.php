<?php

use App\Models\Estuche;
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
    $estuche = Estuche::whereDoesntHave('joya')->firstOrFail();

    $this->postJson('/api/joyas', [
        'tipo' => 'Importada',
        'peso' => 5,
        'linea' => 'Mama',
        'estuche_id' => $estuche->id,
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
    $estuche = Estuche::whereDoesntHave('joya')->firstOrFail();

    $this->getJson('/api/joyas')->assertForbidden();
    $this->postJson('/api/joyas', [
        'tipo' => 'Plata',
        'peso' => 1,
        'linea' => 'Roger',
        'estuche_id' => $estuche->id,
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
        'estuche_id' => Estuche::whereDoesntHave('joya')->firstOrFail()->id,
        'estuche' => 'ESTUCHE BASE',
        'nombre' => 'ANILLO BASE',
        'imagen' => 'default.png',
        'monto_bs' => 500,
    ]);
    $nuevoEstuche = Estuche::whereDoesntHave('joya')
        ->where('id', '!=', $joya->estuche_id)
        ->firstOrFail();

    $this->putJson('/api/joyas/'.$joya->id, [
        'tipo' => 'Plata',
        'peso' => 9.5,
        'linea' => 'Andreina',
        'estuche_id' => $nuevoEstuche->id,
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
