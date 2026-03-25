<?php

use App\Models\DailyCash;
use App\Models\Client;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Orden;
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

it('keeps global totals when filtering libro diario by usuario', function () {
    $admin = User::factory()->create(['role' => 'Administrador', 'username' => 'admin']);
    $userA = User::factory()->create(['role' => 'Vendedor', 'username' => 'roger']);
    $userB = User::factory()->create(['role' => 'Vendedor', 'username' => 'jimmy']);
    Sanctum::actingAs($admin);

    $today = Carbon::today()->toDateString();

    DailyCash::create([
        'date' => $today,
        'opening_amount' => 100,
        'user_id' => $admin->id,
    ]);

    $cliente = Client::create([
        'name' => 'CLIENTE FILTRO',
        'ci' => '445566',
        'status' => 'Confiable',
        'cellphone' => '77774455',
        'address' => 'ORURO',
    ]);

    Orden::create([
        'numero' => 'O0900-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => $today,
        'fecha_entrega' => $today,
        'detalle' => 'ORDEN USUARIO A',
        'celular' => $cliente->cellphone,
        'costo_total' => 400,
        'adelanto' => 200,
        'saldo' => 200,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'Efectivo',
        'user_id' => $userA->id,
        'cliente_id' => $cliente->id,
    ]);

    Ingreso::create([
        'fecha' => $today,
        'descripcion' => 'INGRESO USUARIO B',
        'metodo' => 'EFECTIVO',
        'monto' => 50,
        'estado' => 'Activo',
        'user_id' => $userB->id,
    ]);

    Egreso::create([
        'fecha' => $today,
        'descripcion' => 'EGRESO USUARIO B',
        'metodo' => 'EFECTIVO',
        'monto' => 30,
        'estado' => 'Activo',
        'user_id' => $userB->id,
    ]);

    $this->getJson('/api/daily-cash?date='.$today.'&usuario=roger')
        ->assertOk()
        ->assertJsonPath('total_ingresos', 350)
        ->assertJsonPath('total_egresos', 30)
        ->assertJsonPath('total_caja', 320)
        ->assertJsonCount(1, 'items_ingresos')
        ->assertJsonCount(0, 'items_egresos');
});
