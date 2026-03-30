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

it('creates a direct jewel sale with multiple joyas', function () {
    $user = User::factory()->create(['role' => 'Vendedor']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE MULTIPLE',
        'ci' => '321654',
        'status' => 'Confiable',
        'cellphone' => '76666666',
        'address' => 'ORURO',
    ]);

    $joyas = Joya::query()->take(2)->get();

    $this->postJson('/api/ordenes', [
        'tipo' => 'Venta directa',
        'joya_id' => $joyas[0]->id,
        'joya_ids' => $joyas->pluck('id')->all(),
        'cliente_id' => $cliente->id,
        'celular' => $cliente->cellphone,
        'costo_total' => 9000,
        'adelanto' => 1000,
        'tipo_pago' => 'QR',
    ])->assertCreated()
        ->assertJsonFragment([
            'tipo' => 'Venta directa',
            'joya_id' => $joyas[0]->id,
        ]);

    $orden = Orden::where('tipo', 'Venta directa')->latest('id')->firstOrFail();

    expect($orden->joyas()->pluck('joyas.id')->all())->toBe($joyas->pluck('id')->all());
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

it('excludes every joya from a multi-jewel sale from available sale list until cancellation', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE VARIOS',
        'ci' => '998877',
        'status' => 'Confiable',
        'cellphone' => '78889999',
        'address' => 'ORURO',
    ]);

    $joyas = Joya::query()->take(2)->get();

    $venta = $this->postJson('/api/ordenes', [
        'tipo' => 'Venta directa',
        'joya_id' => $joyas[0]->id,
        'joya_ids' => $joyas->pluck('id')->all(),
        'cliente_id' => $cliente->id,
        'costo_total' => 4000,
        'adelanto' => 500,
        'tipo_pago' => 'Efectivo',
    ])->assertCreated()->json();

    $this->getJson('/api/ordenes/joyas-disponibles')
        ->assertOk()
        ->assertJsonMissing(['id' => $joyas[0]->id])
        ->assertJsonMissing(['id' => $joyas[1]->id]);

    $this->postJson('/api/ordenes/'.$venta['id'].'/cancelar', [
        'anular_pagos' => true,
    ])->assertOk();

    $this->getJson('/api/ordenes/joyas-disponibles')
        ->assertOk()
        ->assertJsonFragment(['id' => $joyas[0]->id])
        ->assertJsonFragment(['id' => $joyas[1]->id]);
});

it('filters available direct-sale joyas by linea', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $joyaMama = Joya::where('linea', 'Mama')->firstOrFail();

    $this->getJson('/api/ordenes/joyas-disponibles?linea=Mama')
        ->assertOk()
        ->assertJsonFragment(['id' => $joyaMama->id])
        ->assertJsonMissing(['linea' => 'Papa']);
});

it('finds available direct-sale joyas by codigo', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Vendedor']));

    $joya = Joya::firstOrFail();
    $codigo = 'J'.str_pad((string) $joya->id, 4, '0', STR_PAD_LEFT);

    $this->getJson('/api/ordenes/joyas-disponibles?search='.$codigo)
        ->assertOk()
        ->assertJsonFragment([
            'id' => $joya->id,
            'codigo' => $codigo,
        ]);
});

it('filters direct sales by exact fecha and linea', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE FILTRO',
        'ci' => '555666',
        'status' => 'Confiable',
        'cellphone' => '70001010',
        'address' => 'ORURO',
    ]);

    $joyaMama = Joya::where('linea', 'Mama')->firstOrFail();
    $joyaPapa = Joya::where('linea', 'Papa')->firstOrFail();

    Orden::create([
        'numero' => 'V0100-2026',
        'tipo' => 'Venta directa',
        'fecha_creacion' => '2026-03-20 10:00:00',
        'fecha_entrega' => '2026-03-20',
        'detalle' => 'VENTA MAMA',
        'celular' => $cliente->cellphone,
        'costo_total' => 1500,
        'adelanto' => 1500,
        'saldo' => 0,
        'estado' => 'Entregado',
        'peso' => $joyaMama->peso,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'joya_id' => $joyaMama->id,
    ]);

    Orden::create([
        'numero' => 'V0101-2026',
        'tipo' => 'Venta directa',
        'fecha_creacion' => '2026-03-21 10:00:00',
        'fecha_entrega' => '2026-03-21',
        'detalle' => 'VENTA PAPA',
        'celular' => $cliente->cellphone,
        'costo_total' => 1800,
        'adelanto' => 1800,
        'saldo' => 0,
        'estado' => 'Entregado',
        'peso' => $joyaPapa->peso,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'joya_id' => $joyaPapa->id,
    ]);

    $this->getJson('/api/ordenes?tipo=Venta%20directa&fecha=2026-03-20&linea=Mama')
        ->assertOk()
        ->assertJsonFragment(['numero' => 'V0100-2026'])
        ->assertJsonMissing(['numero' => 'V0101-2026']);
});

it('lists jewel showcase statuses for ventas joyas filtering', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE ESTADOS',
        'ci' => '999888',
        'status' => 'Confiable',
        'cellphone' => '77700000',
        'address' => 'ORURO',
    ]);

    $joyaReservada = Joya::firstOrFail();
    $joyaVendida = Joya::where('id', '!=', $joyaReservada->id)->firstOrFail();

    Orden::create([
        'numero' => 'V0200-2026',
        'tipo' => 'Venta directa',
        'fecha_creacion' => '2026-03-22 10:00:00',
        'fecha_entrega' => '2026-03-30',
        'detalle' => 'VENTA RESERVADA',
        'celular' => $cliente->cellphone,
        'costo_total' => 2000,
        'adelanto' => 500,
        'saldo' => 1500,
        'estado' => 'Pendiente',
        'peso' => $joyaReservada->peso,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'joya_id' => $joyaReservada->id,
    ]);

    Orden::create([
        'numero' => 'V0201-2026',
        'tipo' => 'Venta directa',
        'fecha_creacion' => '2026-03-23 10:00:00',
        'fecha_entrega' => '2026-03-23',
        'detalle' => 'VENTA ENTREGADA',
        'celular' => $cliente->cellphone,
        'costo_total' => 3000,
        'adelanto' => 3000,
        'saldo' => 0,
        'estado' => 'Entregado',
        'peso' => $joyaVendida->peso,
        'tipo_pago' => 'Efectivo',
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'joya_id' => $joyaVendida->id,
    ]);

    $this->getJson('/api/ordenes/joyas-vitrina?estado_joya=RESERVADO')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $joyaReservada->id,
            'estado_joya' => 'RESERVADO',
        ])
        ->assertJsonMissing([
            'id' => $joyaVendida->id,
            'estado_joya' => 'VENDIDO',
        ]);
});

it('marks every joya in a multi-jewel sale as reservada in vitrina', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE MULTI VITRINA',
        'ci' => '112233',
        'status' => 'Confiable',
        'cellphone' => '70012345',
        'address' => 'ORURO',
    ]);

    $joyas = Joya::query()->take(2)->get();

    $this->postJson('/api/ordenes', [
        'tipo' => 'Venta directa',
        'joya_id' => $joyas[0]->id,
        'joya_ids' => $joyas->pluck('id')->all(),
        'cliente_id' => $cliente->id,
        'costo_total' => 4500,
        'adelanto' => 500,
        'tipo_pago' => 'Efectivo',
    ])->assertCreated();

    $this->getJson('/api/ordenes/joyas-vitrina?estado_joya=RESERVADO')
        ->assertOk()
        ->assertJsonFragment(['id' => $joyas[0]->id, 'estado_joya' => 'RESERVADO'])
        ->assertJsonFragment(['id' => $joyas[1]->id, 'estado_joya' => 'RESERVADO']);
});

it('finds joyas in vitrina by codigo', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $joya = Joya::firstOrFail();
    $codigo = 'J'.str_pad((string) $joya->id, 4, '0', STR_PAD_LEFT);

    $this->getJson('/api/ordenes/joyas-vitrina?search='.$codigo)
        ->assertOk()
        ->assertJsonFragment([
            'id' => $joya->id,
            'codigo' => $codigo,
        ]);
});
