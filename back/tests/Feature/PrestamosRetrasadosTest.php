<?php

use App\Models\Client;
use App\Models\Prestamo;
use App\Models\PrestamoPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists overdue loans with summary and pagination metadata', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE RETRASADO',
        'ci' => '1234567',
        'status' => 'Confiable',
        'cellphone' => '77770000',
        'address' => 'ORURO',
    ]);

    Prestamo::create([
        'numero' => 'P0001-2026',
        'fecha_creacion' => now()->subDays(20),
        'fecha_limite' => now()->subDays(10)->toDateString(),
        'fecha_cancelacion' => now()->addDays(20)->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 5,
        'merma' => 0,
        'peso_neto' => 5,
        'precio_oro' => 1000,
        'valor_total' => 5000,
        'valor_prestado' => 2000,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '77770000',
        'detalle' => 'PRESTAMO EN RETRASO',
        'estado' => 'Activo',
    ]);

    Prestamo::create([
        'numero' => 'P0002-2026',
        'fecha_creacion' => now()->subDays(25),
        'fecha_limite' => now()->subDays(12)->toDateString(),
        'fecha_cancelacion' => now()->addDays(18)->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 3,
        'merma' => 0,
        'peso_neto' => 3,
        'precio_oro' => 1000,
        'valor_total' => 3000,
        'valor_prestado' => 1200,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '77770000',
        'detalle' => 'PRESTAMO ENTREGADO',
        'estado' => 'Entregado',
    ]);

    Prestamo::create([
        'numero' => 'P0003-2026',
        'fecha_creacion' => now()->subDays(25),
        'fecha_limite' => now()->subDays(12)->toDateString(),
        'fecha_cancelacion' => now()->addDays(18)->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 3,
        'merma' => 0,
        'peso_neto' => 3,
        'precio_oro' => 1000,
        'valor_total' => 3000,
        'valor_prestado' => 1200,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '77770000',
        'detalle' => 'PRESTAMO FUNDIDO',
        'estado' => 'Fundido',
    ]);

    $this->getJson('/api/prestamosRetrasados?dias=1&per_page=10')
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('summary.capital_invertido', 2000)
        ->assertJsonPath('summary.capital_detalle.0.numero', 'P0001-2026')
        ->assertJsonPath('summary.capital_detalle.0.valor_prestado', 2000)
        ->assertJsonFragment([
            'numero' => 'P0001-2026',
            'detalle' => 'PRESTAMO EN RETRASO',
        ])
        ->assertJsonMissing([
            'numero' => 'P0002-2026',
        ])
        ->assertJsonMissing([
            'numero' => 'P0003-2026',
        ]);
});

it('calculates invested capital discounting capital payments', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE ABONO',
        'ci' => '5555555',
        'status' => 'Confiable',
        'cellphone' => '77772222',
        'address' => 'ORURO',
    ]);

    $prestamo = Prestamo::create([
        'numero' => 'P0010-2026',
        'fecha_creacion' => now()->subDays(40),
        'fecha_limite' => now()->subDays(8)->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 5,
        'merma' => 0,
        'peso_neto' => 5,
        'precio_oro' => 1000,
        'valor_total' => 5000,
        'valor_prestado' => 3000,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '77772222',
        'detalle' => 'CON ABONO A CAPITAL',
        'estado' => 'Activo',
    ]);

    $base = ['fecha' => now()->toDateString(), 'user_id' => $admin->id, 'prestamo_id' => $prestamo->id, 'metodo' => 'EFECTIVO'];
    PrestamoPago::create([...$base, 'monto' => 1000, 'estado' => 'Activo', 'tipo_pago' => 'SALDO']);
    PrestamoPago::create([...$base, 'monto' => 200, 'estado' => 'Activo', 'tipo_pago' => 'CARGOS']);
    PrestamoPago::create([...$base, 'monto' => 500, 'estado' => 'Anulado', 'tipo_pago' => 'SALDO']);

    $this->getJson('/api/prestamosRetrasados?dias=1')
        ->assertOk()
        ->assertJsonPath('summary.capital_prestado', 3000)
        ->assertJsonPath('summary.capital_pagado', 1000)
        ->assertJsonPath('summary.capital_invertido', 2000)
        ->assertJsonPath('summary.capital_detalle.0.capital_pendiente', 2000);

    $content = $this->get('/api/prestamosRetrasados/export?dias=1')->streamedContent();
    expect($content)
        ->toContain('Capital invertido (pendiente)')
        ->toContain('<td>2000.00</td>')
        ->toContain('TOTAL (1 prestamos)');
});

it('exports overdue loans as excel-compatible file with weight and gold price', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE CSV',
        'ci' => '7654321',
        'status' => 'Confiable',
        'cellphone' => '77771111',
        'address' => 'ORURO',
    ]);

    Prestamo::create([
        'numero' => 'P0002-2026',
        'fecha_creacion' => now()->subDays(30),
        'fecha_limite' => now()->subDays(5)->toDateString(),
        'fecha_cancelacion' => now()->addDays(25)->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 4,
        'merma' => 0.2,
        'peso_neto' => 3.8,
        'precio_oro' => 1000,
        'valor_total' => 3800,
        'valor_prestado' => 1500,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '77771111',
        'detalle' => 'PRESTAMO CSV',
        'estado' => 'Activo',
    ]);

    $response = $this->get('/api/prestamosRetrasados/export?dias=1');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/vnd.ms-excel');
    expect($response->streamedContent())->toContain('P0002-2026');
    expect($response->streamedContent())->toContain('CLIENTE CSV');
    expect($response->streamedContent())->toContain('4.00');
    expect($response->streamedContent())->toContain('1000.00');
});
