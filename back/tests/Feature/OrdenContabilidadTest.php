<?php

use App\Models\Client;
use App\Models\Egreso;
use App\Models\Orden;
use App\Models\OrdenPago;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers an egreso when an order is cancelled with reversed payments', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE CANCELACION',
        'ci' => '100200',
        'status' => 'Confiable',
        'cellphone' => '70010020',
        'address' => 'ORURO',
    ]);

    $orden = Orden::create([
        'numero' => 'O0100-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ORDEN A CANCELAR',
        'celular' => $cliente->cellphone,
        'costo_total' => 1000,
        'adelanto' => 300,
        'saldo' => 700,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'QR',
        'user_id' => $admin->id,
        'cliente_id' => $cliente->id,
    ]);

    OrdenPago::create([
        'fecha' => now(),
        'monto' => 200,
        'metodo' => 'Efectivo',
        'estado' => 'Activo',
        'user_id' => $admin->id,
        'orden_id' => $orden->id,
    ]);

    $this->postJson("/api/ordenes/{$orden->id}/cancelar", [
        'anular_pagos' => true,
    ])->assertOk();

    $orden->refresh();

    expect($orden->estado)->toBe('Cancelada');
    expect((float) $orden->adelanto)->toBe(0.0);
    expect((float) $orden->saldo)->toBe(1000.0);

    $this->assertDatabaseHas('egresos', [
        'descripcion' => 'ANULACION ORDEN O0100-2026',
        'monto' => 500,
        'metodo' => 'QR',
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);
});

it('registers an egreso when an order payment is annulled', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE ADELANTO',
        'ci' => '300400',
        'status' => 'Confiable',
        'cellphone' => '70030040',
        'address' => 'ORURO',
    ]);

    $orden = Orden::create([
        'numero' => 'O0101-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ORDEN CON ADELANTO',
        'celular' => $cliente->cellphone,
        'costo_total' => 900,
        'adelanto' => 150,
        'saldo' => 750,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'Efectivo',
        'user_id' => $admin->id,
        'cliente_id' => $cliente->id,
    ]);

    $pago = OrdenPago::create([
        'fecha' => now(),
        'monto' => 150,
        'metodo' => 'QR',
        'estado' => 'Activo',
        'user_id' => $admin->id,
        'orden_id' => $orden->id,
    ]);

    $this->postJson("/api/ordenes/pagos/{$pago->id}/anular")
        ->assertOk()
        ->assertJsonFragment(['message' => 'Pago anulado']);

    $this->assertDatabaseHas('orden_pagos', [
        'id' => $pago->id,
        'estado' => 'Anulado',
    ]);

    $this->assertDatabaseHas('egresos', [
        'descripcion' => 'ANULACION ADELANTO ORDEN O0101-2026',
        'monto' => 150,
        'metodo' => 'QR',
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);
});

it('keeps loan interest unchanged even for admin updates', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE PRESTAMO',
        'ci' => '500600',
        'status' => 'Confiable',
        'cellphone' => '70050060',
        'address' => 'ORURO',
    ]);

    $prestamo = Prestamo::create([
        'numero' => 'P0100-2026',
        'fecha_creacion' => now(),
        'fecha_limite' => now()->addMonth()->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 10,
        'merma' => 1,
        'peso_neto' => 9,
        'precio_oro' => 100,
        'valor_total' => 900,
        'valor_prestado' => 500,
        'interes' => 2,
        'almacen' => 1,
        'celular' => $cliente->cellphone,
        'detalle' => 'PRESTAMO PRUEBA',
        'estado' => 'Pendiente',
    ]);

    $this->putJson("/api/prestamos/{$prestamo->id}", [
        'interes' => 3,
        'almacen' => 2,
        'detalle' => 'PRESTAMO EDITADO',
    ])->assertOk();

    $prestamo->refresh();

    expect((float) $prestamo->interes)->toBe(2.0);
    expect((float) $prestamo->almacen)->toBe(2.0);
});

it('recalculates order saldo without changing adelanto when admin updates price', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE PRECIO',
        'ci' => '700800',
        'status' => 'Confiable',
        'cellphone' => '70070080',
        'address' => 'ORURO',
    ]);

    $orden = Orden::create([
        'numero' => 'O0102-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ORDEN PRECIO',
        'celular' => $cliente->cellphone,
        'costo_total' => 1000,
        'adelanto' => 300,
        'saldo' => 700,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'Efectivo',
        'user_id' => $admin->id,
        'cliente_id' => $cliente->id,
    ]);

    OrdenPago::create([
        'fecha' => now(),
        'monto' => 100,
        'metodo' => 'Efectivo',
        'estado' => 'Activo',
        'user_id' => $admin->id,
        'orden_id' => $orden->id,
    ]);

    $this->putJson("/api/ordenes/{$orden->id}", [
        'costo_total' => 1500,
        'adelanto' => 999,
    ])->assertOk();

    $orden->refresh();

    expect((float) $orden->adelanto)->toBe(300.0);
    expect((float) $orden->saldo)->toBe(1100.0);
    expect($orden->estado)->toBe('Pendiente');
});
