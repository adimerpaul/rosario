<?php

use App\Models\Client;
use App\Models\Joya;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists available joyas for direct sale', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $this->getJson('/api/ordenes/joyas-disponibles')
        ->assertOk()
        ->assertJsonCount(5);
});

it('creates a direct jewel sale in ordenes table', function () {
    $user = User::factory()->create(['role' => 'Vendedor']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE PRUEBA',
        'ci' => '123456',
        'status' => 'Confiable',
        'cellphone' => '77777777',
        'address' => 'ORURO',
    ]);

    $joya = Joya::firstOrFail();

    $this->postJson('/api/ordenes', [
        'tipo' => 'Venta directa',
        'joya_id' => $joya->id,
        'cliente_id' => $cliente->id,
        'celular' => $cliente->cellphone,
        'costo_total' => 6750,
        'adelanto' => 1350,
        'tipo_pago' => 'QR',
    ])->assertCreated()
        ->assertJsonFragment([
            'tipo' => 'Venta directa',
            'joya_id' => $joya->id,
            'cliente_id' => $cliente->id,
        ]);

    $this->assertDatabaseHas('ordenes', [
        'tipo' => 'Venta directa',
        'joya_id' => $joya->id,
        'cliente_id' => $cliente->id,
        'user_id' => $user->id,
    ]);

    expect(Orden::where('tipo', 'Venta directa')->firstOrFail()->numero)->toStartWith('V');
});

it('marks a direct jewel sale as entregado when fully paid at creation', function () {
    $user = User::factory()->create(['role' => 'Vendedor']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE CONTADO',
        'ci' => '789456',
        'status' => 'Confiable',
        'cellphone' => '70000000',
        'address' => 'ORURO',
    ]);

    $joya = Joya::where('id', '!=', 1)->firstOrFail();

    $this->postJson('/api/ordenes', [
        'tipo' => 'Venta directa',
        'joya_id' => $joya->id,
        'cliente_id' => $cliente->id,
        'costo_total' => 2700,
        'adelanto' => 2700,
        'tipo_pago' => 'Efectivo',
    ])->assertCreated()
        ->assertJsonFragment([
            'estado' => 'Entregado',
            'saldo' => 0,
        ]);
});

it('excludes already sold joyas from available sale list until cancellation', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE PRUEBA',
        'ci' => '123456',
        'status' => 'Confiable',
        'cellphone' => '77777777',
        'address' => 'ORURO',
    ]);

    $joya = Joya::firstOrFail();

    $venta = Orden::create([
        'numero' => 'V0001-2026',
        'tipo' => 'Venta directa',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'VENTA DIRECTA',
        'celular' => $cliente->cellphone,
        'costo_total' => 1000,
        'adelanto' => 1000,
        'saldo' => 0,
        'estado' => 'Entregado',
        'peso' => $joya->peso,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'joya_id' => $joya->id,
    ]);

    $this->getJson('/api/ordenes/joyas-disponibles')
        ->assertOk()
        ->assertJsonMissing(['id' => $joya->id]);

    $this->postJson("/api/ordenes/{$venta->id}/cancelar", [
        'anular_pagos' => true,
    ])->assertOk();

    $this->getJson('/api/ordenes/joyas-disponibles')
        ->assertOk()
        ->assertJsonFragment(['id' => $joya->id]);
});
