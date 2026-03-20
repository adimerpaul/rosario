<?php

use App\Models\Client;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('shows daily cash without querying missing metodo_entrega column on prestamos', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);
    $today = Carbon::today();
    $yesterday = $today->copy()->subDay();

    $cliente = Client::create([
        'name' => 'CLIENTE CAJA',
        'ci' => '9988776',
        'status' => 'Confiable',
        'cellphone' => '77772222',
        'address' => 'ORURO',
    ]);

    $prestamo = Prestamo::create([
        'numero' => 'PR-000001-2026',
        'fecha_creacion' => $yesterday->toDateString(),
        'fecha_limite' => $yesterday->toDateString(),
        'fecha_cancelacion' => $today->copy()->addMonth()->toDateString(),
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
        'celular' => '77772222',
        'detalle' => 'PRESTAMO LIBRO DIARIO',
        'estado' => 'Activo',
    ]);

    $prestamo->timestamps = false;
    $prestamo->forceFill([
        'created_at' => $yesterday->copy()->setTime(10, 0, 0),
        'updated_at' => $yesterday->copy()->setTime(10, 0, 0),
    ])->save();

    $this->getJson('/api/daily-cash?date='.$today->toDateString())
        ->assertOk()
        ->assertJsonPath('suggested_opening_amount', -2000)
        ->assertJsonPath('total_egresos', 0)
        ->assertJsonPath('daily_cash.opening_amount', -2000);
});
