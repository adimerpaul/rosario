<?php

use App\Models\Client;
use App\Models\Orden;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers warehouse entry and exit for an order', function () {
    $user = User::factory()->create(['role' => 'Vendedor']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE ALMACEN',
        'ci' => '1234567',
        'status' => 'Confiable',
        'cellphone' => '77771111',
        'address' => 'ORURO',
    ]);

    $orden = Orden::create([
        'numero' => 'O0001-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->addDays(2)->toDateString(),
        'detalle' => 'ANILLO DE PRUEBA',
        'celular' => '77771111',
        'costo_total' => 1500,
        'adelanto' => 300,
        'saldo' => 1200,
        'estado' => 'Pendiente',
        'peso' => 4,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
    ]);

    $prestamo = Prestamo::create([
        'numero' => 'PR-000001-2026',
        'fecha_creacion' => now(),
        'fecha_limite' => now()->toDateString(),
        'fecha_cancelacion' => now()->addMonth()->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $user->id,
        'peso' => 12,
        'merma' => 1,
        'peso_neto' => 11,
        'precio_oro' => 500,
        'valor_total' => 5500,
        'valor_prestado' => 2000,
        'interes' => 2,
        'almacen' => 1,
        'saldo' => 2060,
        'celular' => '77771111',
        'detalle' => 'PRESTAMO ASOCIADO',
        'estado' => 'Activo',
    ]);

    $this->postJson('/api/almacen/entradas', [
        'orden_id' => $orden->id,
        'prestamo_id' => $prestamo->id,
        'observacion' => 'Ingreso inicial',
    ])
        ->assertCreated()
        ->assertJsonFragment([
            'tipo_movimiento' => 'ENTRADA',
            'orden_numero' => $orden->numero,
            'observacion' => 'Ingreso inicial',
        ]);

    $this->getJson('/api/almacen')
        ->assertOk()
        ->assertJsonPath('summary.cantidad_actual', 1)
        ->assertJsonPath('actuales.0.orden_numero', $orden->numero)
        ->assertJsonPath('actuales.0.observacion', 'Ingreso inicial');

    $this->postJson('/api/almacen/salidas', [
        'orden_id' => $orden->id,
        'observacion' => 'Entrega al cliente',
    ])
        ->assertCreated()
        ->assertJsonFragment([
            'tipo_movimiento' => 'SALIDA',
            'orden_numero' => $orden->numero,
            'observacion' => 'Entrega al cliente',
        ]);

    $this->getJson('/api/almacen')
        ->assertOk()
        ->assertJsonPath('summary.cantidad_actual', 0)
        ->assertJsonCount(2, 'historial');

    $this->assertDatabaseCount('almacen_movimientos', 2);
});
