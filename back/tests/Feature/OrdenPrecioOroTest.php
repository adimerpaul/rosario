<?php

use App\Models\Client;
use App\Models\Cog;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('stores the current gold price on new work orders', function () {
    $user = User::factory()->create(['role' => 'Vendedor']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE ORO',
        'ci' => '998877',
        'status' => 'Confiable',
        'cellphone' => '77778888',
        'address' => 'ORURO',
    ]);

    Cog::query()->insert([
        'id' => 2,
        'name' => 'Precio Oro',
        'value' => 1234.5,
        'description' => 'Precio actual oro ordenes',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/ordenes', [
        'tipo' => 'Orden',
        'cliente_id' => $cliente->id,
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ORDEN CON PRECIO ORO',
        'celular' => $cliente->cellphone,
        'peso' => 2,
        'costo_total' => 2469,
        'adelanto' => 100,
        'saldo' => 2369,
        'tipo_pago' => 'Efectivo',
    ])->assertCreated()
        ->assertJsonPath('precio_oro_historico', 1234.5)
        ->assertJsonPath('costo_total_oro', 2469);

    $this->assertDatabaseHas('ordenes', [
        'cliente_id' => $cliente->id,
        'tipo' => 'Orden',
        'precio_oro' => 1234.5,
    ]);
});

it('falls back to 1080 as historical gold price for old work orders without stored value', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE HISTORICO',
        'ci' => '112244',
        'status' => 'Confiable',
        'cellphone' => '77770001',
        'address' => 'ORURO',
    ]);

    $orden = Orden::query()->create([
        'numero' => 'O0001-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ORDEN HISTORICA',
        'celular' => $cliente->cellphone,
        'costo_total' => 500,
        'adelanto' => 100,
        'saldo' => 400,
        'estado' => 'Pendiente',
        'peso' => 1.5,
        'precio_oro' => null,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
    ]);

    $this->getJson("/api/ordenes/{$orden->id}")
        ->assertOk()
        ->assertJsonPath('precio_oro_historico', 1080)
        ->assertJsonPath('costo_total_oro', 1620);
});
