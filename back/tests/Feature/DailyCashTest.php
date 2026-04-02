<?php

use App\Models\DailyCash;
use App\Models\Client;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Orden;
use App\Models\OrdenPago;
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

it('shows only the selected user totals when filtering libro diario by usuario', function () {
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
        ->assertJsonPath('filtered_opening_amount', 0)
        ->assertJsonPath('is_user_filtered', true)
        ->assertJsonPath('total_ingresos', 200)
        ->assertJsonPath('total_egresos', 0)
        ->assertJsonPath('total_caja', 200)
        ->assertJsonCount(1, 'items_ingresos')
        ->assertJsonCount(0, 'items_egresos');
});

it('uses the previous day net total as opening amount for the next day', function () {
    $admin = User::factory()->create(['role' => 'Administrador', 'username' => 'admin']);
    Sanctum::actingAs($admin);

    $yesterday = Carbon::today()->subDay()->toDateString();
    $today = Carbon::today()->toDateString();

    $cliente = Client::create([
        'name' => 'CLIENTE ARRASTRE',
        'ci' => '778899',
        'status' => 'Confiable',
        'cellphone' => '77779999',
        'address' => 'ORURO',
    ]);

    $orden = Orden::create([
        'numero' => 'O0999-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => $yesterday,
        'fecha_entrega' => $yesterday,
        'detalle' => 'ORDEN PARA ARRASTRE',
        'celular' => $cliente->cellphone,
        'costo_total' => 1000,
        'adelanto' => 200,
        'saldo' => 800,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'Efectivo',
        'user_id' => $admin->id,
        'cliente_id' => $cliente->id,
    ]);

    DailyCash::create([
        'date' => $yesterday,
        'opening_amount' => 1000,
        'user_id' => $admin->id,
    ]);

    OrdenPago::create([
        'orden_id' => $orden->id,
        'fecha' => $yesterday,
        'monto' => 300,
        'metodo' => 'QR',
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);

    Ingreso::create([
        'fecha' => $yesterday,
        'descripcion' => 'INGRESO QR',
        'metodo' => 'QR',
        'monto' => 400,
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);

    Prestamo::create([
        'numero' => 'PR-000900-2026',
        'fecha_creacion' => $yesterday,
        'fecha_limite' => $today,
        'fecha_cancelacion' => Carbon::today()->addMonth()->toDateString(),
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'peso' => 5,
        'merma' => 0,
        'peso_neto' => 5,
        'precio_oro' => 1000,
        'valor_total' => 5000,
        'valor_prestado' => 250,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '77772222',
        'detalle' => 'PRESTAMO QR',
        'estado' => 'Activo',
    ]);

    Egreso::create([
        'fecha' => $yesterday,
        'descripcion' => 'EGRESO EFECTIVO',
        'metodo' => 'EFECTIVO',
        'monto' => 100,
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);

    $expectedNet = 1000 + 200 - 100;

    $this->getJson('/api/daily-cash?date='.$today)
        ->assertOk()
        ->assertJsonPath('suggested_opening_amount', $expectedNet)
        ->assertJsonPath('daily_cash.opening_amount', $expectedNet);
});

it('carries forward the last available cash total across days without daily cash records', function () {
    $admin = User::factory()->create(['role' => 'Administrador', 'username' => 'admin']);
    Sanctum::actingAs($admin);

    $baseDate = Carbon::today()->subDays(3)->toDateString();
    $targetDate = Carbon::today()->toDateString();

    $cliente = Client::create([
        'name' => 'CLIENTE SALTO',
        'ci' => '112233',
        'status' => 'Confiable',
        'cellphone' => '70001122',
        'address' => 'ORURO',
    ]);

    DailyCash::create([
        'date' => $baseDate,
        'opening_amount' => 500,
        'user_id' => $admin->id,
    ]);

    Ingreso::create([
        'fecha' => $baseDate,
        'descripcion' => 'INGRESO BASE',
        'metodo' => 'EFECTIVO',
        'monto' => 120,
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);

    Egreso::create([
        'fecha' => Carbon::parse($baseDate)->addDay()->toDateString(),
        'descripcion' => 'EGRESO INTERMEDIO',
        'metodo' => 'EFECTIVO',
        'monto' => 20,
        'estado' => 'Activo',
        'user_id' => $admin->id,
    ]);

    Orden::create([
        'numero' => 'O0800-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => Carbon::parse($baseDate)->addDays(2)->toDateString(),
        'fecha_entrega' => Carbon::parse($baseDate)->addDays(2)->toDateString(),
        'detalle' => 'ORDEN INTERMEDIA',
        'celular' => $cliente->cellphone,
        'costo_total' => 500,
        'adelanto' => 200,
        'saldo' => 300,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'Efectivo',
        'user_id' => $admin->id,
        'cliente_id' => $cliente->id,
    ]);

    $expectedNet = 500 + 120 - 20 + 200;

    $this->getJson('/api/daily-cash?date='.$targetDate)
        ->assertOk()
        ->assertJsonPath('suggested_opening_amount', $expectedNet)
        ->assertJsonPath('daily_cash.opening_amount', $expectedNet);
});

it('refreshes an existing opening amount when the next day has no movements yet', function () {
    $admin = User::factory()->create(['role' => 'Administrador', 'username' => 'admin']);
    Sanctum::actingAs($admin);

    $yesterday = Carbon::create(2026, 4, 1)->toDateString();
    $today = Carbon::create(2026, 4, 2)->toDateString();

    $cliente = Client::create([
        'name' => 'CLIENTE CAJA SIGUIENTE',
        'ci' => '556677',
        'status' => 'Confiable',
        'cellphone' => '77770000',
        'address' => 'ORURO',
    ]);

    DailyCash::create([
        'date' => $yesterday,
        'opening_amount' => 14631.44,
        'user_id' => $admin->id,
    ]);

    DailyCash::create([
        'date' => $today,
        'opening_amount' => 17270.82,
        'user_id' => $admin->id,
    ]);

    foreach ([
        ['fecha' => $yesterday, 'descripcion' => 'aspel', 'monto' => 4300],
        ['fecha' => $yesterday, 'descripcion' => 'r martes', 'monto' => 3285],
    ] as $ingreso) {
        Ingreso::create([
            'fecha' => $ingreso['fecha'],
            'descripcion' => $ingreso['descripcion'],
            'metodo' => 'EFECTIVO',
            'monto' => $ingreso['monto'],
            'estado' => 'Activo',
            'user_id' => $admin->id,
        ]);
    }

    foreach ([
        ['numero' => 'PR-000394-2026', 'monto' => 1200, 'usuario' => $admin->id],
        ['numero' => 'PR-000395-2026', 'monto' => 1200, 'usuario' => $admin->id],
        ['numero' => 'PR-000396-2026', 'monto' => 1700, 'usuario' => $admin->id],
    ] as $index => $prestamoData) {
        $prestamo = Prestamo::create([
            'numero' => $prestamoData['numero'],
            'fecha_creacion' => $yesterday,
            'fecha_limite' => $today,
            'fecha_cancelacion' => Carbon::parse($today)->addMonth()->toDateString(),
            'cliente_id' => $cliente->id,
            'user_id' => $prestamoData['usuario'],
            'peso' => 5 + $index,
            'merma' => 0,
            'peso_neto' => 5 + $index,
            'precio_oro' => 1000,
            'valor_total' => 5000,
            'valor_prestado' => $prestamoData['monto'],
            'interes' => 3,
            'almacen' => 2,
            'celular' => '77770000',
            'detalle' => 'PRESTAMO CAJA',
            'estado' => 'Activo',
        ]);

        $prestamo->timestamps = false;
        $prestamo->forceFill([
            'created_at' => Carbon::parse($yesterday)->setTime(9 + $index, 0, 0),
            'updated_at' => Carbon::parse($yesterday)->setTime(9 + $index, 0, 0),
        ])->save();
    }

    foreach ([
        ['monto' => 1620, 'descripcion' => 'C anillo cuadrado'],
        ['monto' => 2050, 'descripcion' => 'C anillo jorge obligas'],
        ['monto' => 3772, 'descripcion' => 'Anillo infanteria y aretes cisne'],
        ['monto' => 2300, 'descripcion' => 'C ARETE MOSCA'],
        ['monto' => 1970, 'descripcion' => 'C ANILLO POLICIA OVAL'],
        ['monto' => 321, 'descripcion' => 'R LIBRO DIARIO INICIO CAJA'],
    ] as $egreso) {
        Egreso::create([
            'fecha' => $yesterday,
            'descripcion' => $egreso['descripcion'],
            'metodo' => 'EFECTIVO',
            'monto' => $egreso['monto'],
            'estado' => 'Activo',
            'user_id' => $admin->id,
        ]);
    }

    foreach ([
        ['numero' => 'PR-000155-2026', 'monto' => 152.38],
        ['numero' => 'PR-000155-2026', 'monto' => 1000],
        ['numero' => 'PR-000332-2026', 'monto' => 100],
        ['numero' => 'PR-000365-2026', 'monto' => 75],
    ] as $index => $pagoData) {
        $prestamo = Prestamo::create([
            'numero' => $pagoData['numero'],
            'fecha_creacion' => Carbon::parse($yesterday)->subDays(10)->toDateString(),
            'fecha_limite' => $yesterday,
            'fecha_cancelacion' => Carbon::parse($today)->addMonth()->toDateString(),
            'cliente_id' => $cliente->id,
            'user_id' => $admin->id,
            'peso' => 10 + $index,
            'merma' => 0,
            'peso_neto' => 10 + $index,
            'precio_oro' => 1000,
            'valor_total' => 5000,
            'valor_prestado' => 2000,
            'interes' => 3,
            'almacen' => 2,
            'celular' => '77770000',
            'detalle' => 'PRESTAMO PAGO',
            'estado' => 'Activo',
        ]);

        $prestamo->timestamps = false;
        $prestamo->forceFill([
            'created_at' => Carbon::parse($yesterday)->subDays(10)->setTime(10 + $index, 0, 0),
            'updated_at' => Carbon::parse($yesterday)->subDays(10)->setTime(10 + $index, 0, 0),
        ])->save();

        \App\Models\PrestamoPago::create([
            'prestamo_id' => $prestamo->id,
            'fecha' => $yesterday,
            'monto' => $pagoData['monto'],
            'metodo' => 'EFECTIVO',
            'estado' => 'Activo',
            'user_id' => $admin->id,
        ]);
    }

    $expectedNet = 7410.82;

    $this->getJson('/api/daily-cash?date='.$today)
        ->assertOk()
        ->assertJsonPath('suggested_opening_amount', $expectedNet)
        ->assertJsonPath('daily_cash.opening_amount', $expectedNet)
        ->assertJsonPath('total_caja', $expectedNet);
});
