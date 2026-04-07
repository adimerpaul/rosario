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
    $estuche = Estuche::firstOrFail();

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

it('allows an admin to create a joya without estuche', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $this->postJson('/api/joyas', [
        'tipo' => 'Importada',
        'peso' => 2.5,
        'linea' => 'Mama',
        'estuche_id' => null,
        'nombre' => 'Dije sin estuche',
        'monto_bs' => 1200,
    ])->assertCreated()
        ->assertJsonFragment([
            'nombre' => 'DIJE SIN ESTUCHE',
        ]);

    $this->assertDatabaseHas('joyas', [
        'nombre' => 'DIJE SIN ESTUCHE',
        'estuche_id' => null,
        'estuche' => null,
    ]);
});

it('forbids a vendedor from accessing joyas crud', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));
    $estuche = Estuche::firstOrFail();

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

it('searches joyas from the database and not only from the current page', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    Joya::query()->delete();

    $estuche = Estuche::firstOrFail();

    foreach (range(1, 13) as $index) {
        Joya::create([
            'tipo' => 'Importada',
            'peso' => 1,
            'linea' => 'Mama',
            'estuche_id' => $estuche->id,
            'estuche' => $estuche->nombre,
            'nombre' => 'ANILLO '.$index,
            'imagen' => 'joya.png',
            'monto_bs' => 100 + $index,
        ]);
    }

    Joya::create([
        'tipo' => 'Plata',
        'peso' => 2,
        'linea' => 'Roger',
        'estuche_id' => $estuche->id,
        'estuche' => $estuche->nombre,
        'nombre' => 'COLLAR UNICO BUSCADO',
        'imagen' => 'joya.png',
        'monto_bs' => 999,
    ]);

    $this->getJson('/api/joyas?per_page=12&search=UNICO')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nombre', 'COLLAR UNICO BUSCADO');
});

it('allows an admin to update and delete a joya', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $joya = Joya::create([
        'tipo' => 'Importada',
        'peso' => 4,
        'linea' => 'Papa',
        'estuche_id' => Estuche::firstOrFail()->id,
        'estuche' => 'ESTUCHE BASE',
        'nombre' => 'ANILLO BASE',
        'imagen' => 'default.png',
        'monto_bs' => 500,
    ]);
    $nuevoEstuche = Estuche::where('id', '!=', $joya->estuche_id)->firstOrFail();

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

it('allows an admin to remove a joya from its estuche', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $joya = Joya::firstOrFail();

    $this->postJson('/api/joyas/'.$joya->id.'/quitar-estuche')
        ->assertOk();

    $this->assertDatabaseHas('joyas', [
        'id' => $joya->id,
        'estuche_id' => null,
        'estuche' => null,
    ]);
});
