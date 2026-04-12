<?php

use App\Models\Client;
use App\Models\Cog;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function crearClientePrestamo(): Client
{
    return Client::create([
        'name' => 'CLIENTE ALMACEN',
        'ci' => '900100',
        'cellphone' => '70090010',
        'status' => 'Confiable',
        'address' => 'ORURO',
    ]);
}

function asegurarCogPrestamo(): void
{
    Cog::query()->updateOrCreate(
        ['name' => 'Prestamos para comprar'],
        ['value' => 900.00, 'description' => 'Precio compra para prestamos']
    );
}

it('forces almacen to 2 percent for non admin when loan amount is above 1000', function () {
    $vendedor = User::factory()->create(['role' => 'Vendedor']);
    $cliente = crearClientePrestamo();
    asegurarCogPrestamo();

    Sanctum::actingAs($vendedor);

    $this->postJson('/api/prestamos', [
        'cliente_id' => $cliente->id,
        'user_id' => $vendedor->id,
        'fecha_limite' => now()->addMonth()->toDateString(),
        'peso' => 10,
        'merma' => 1,
        'valor_prestado' => 1500,
        'interes' => 3,
        'almacen' => 1,
        'celular' => '70090010',
        'detalle' => 'PRUEBA REGLA ALMACEN',
    ])->assertCreated()
        ->assertJsonPath('almacen', 2);

    $this->assertDatabaseHas('prestamos', [
        'cliente_id' => $cliente->id,
        'valor_prestado' => 1500,
        'almacen' => 2,
    ]);
});

it('allows admin to override almacen when creating a loan above 1000', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    $cliente = crearClientePrestamo();
    asegurarCogPrestamo();

    Sanctum::actingAs($admin);

    $this->postJson('/api/prestamos', [
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'fecha_limite' => now()->addMonth()->toDateString(),
        'peso' => 10,
        'merma' => 1,
        'valor_prestado' => 1500,
        'interes' => 3,
        'almacen' => 1,
        'celular' => '70090010',
        'detalle' => 'PRUEBA ADMIN ALMACEN',
    ])->assertCreated()
        ->assertJsonPath('almacen', 1);
});

it('allows admin to update almacen percentage when editing a loan', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    $cliente = crearClientePrestamo();

    Sanctum::actingAs($admin);

    $prestamo = Prestamo::create([
        'numero' => 'PR-000001-2026',
        'fecha_creacion' => now(),
        'fecha_limite' => now()->addMonth()->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 10,
        'merma' => 1,
        'peso_neto' => 9,
        'precio_oro' => 900,
        'valor_total' => 8100,
        'valor_prestado' => 1500,
        'interes' => 3,
        'almacen' => 2,
        'saldo' => 1575,
        'celular' => $cliente->cellphone,
        'detalle' => 'PRESTAMO EDITABLE',
        'estado' => 'Activo',
    ]);

    $this->putJson("/api/prestamos/{$prestamo->id}", [
        'fecha_creacion' => '2026-04-01',
        'fecha_limite' => '2026-05-01',
        'interes' => 3,
        'almacen' => 1.5,
    ])->assertOk()
        ->assertJsonPath('almacen', 1.5);

    $prestamo->refresh();

    expect((float) $prestamo->almacen)->toBe(1.5);
});
