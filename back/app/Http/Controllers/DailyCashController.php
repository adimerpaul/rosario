<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use App\Models\User;
use Illuminate\Http\Request;

class DailyCashController extends Controller
{
    public function show(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        // Param opcional para filtrar por usuario (username)
        $username = $request->input('usuario', null);
        $user = $username ? User::where('username', $username)->first() : null;
        $usuario = $request->user();
        if ($usuario->role !== 'Administrador') {
            $user = $usuario;
        }

        // 1) Caja del día (no se filtra por usuario)
        $daily = DailyCash::firstOrCreate(
            ['date' => $date],
            ['opening_amount' => 0, 'user_id' => optional($request->user())->id]
        );

        // Si se mandó username pero no existe, devolvemos estructura vacía con totales en 0 (caja inicial se mantiene)
        if ($username && !$user) {
            return response()->json([
                'date'           => $date,
                'daily_cash'     => $daily,
                'ingresos'       => [
                    'ordenes'         => ['total' => 0.0, 'items' => collect()],
                    'pagos_ordenes'   => ['total' => 0.0, 'items' => collect()],
                    'pagos_prestamos' => ['total' => 0.0, 'items' => collect()],
                    'otros'           => ['total' => 0.0, 'items' => collect()],
                ],
                'egresos'        => [
                    'prestamos' => ['total' => 0.0, 'items' => collect()],
                    'otros'     => ['total' => 0.0, 'items' => collect()],
                ],
                'totales_metodo' => [
                    'ingresos' => ['EFECTIVO' => 0.0, 'QR' => 0.0],
                    'egresos'  => ['EFECTIVO' => 0.0, 'QR' => 0.0],
                    'neto'     => ['EFECTIVO' => 0.0, 'QR' => 0.0],
                ],
                'total_ingresos' => (float) $daily->opening_amount, // caja inicial
                'total_egresos'  => 0.0,
                'total_caja'     => (float) $daily->opening_amount,
            ]);
        }

        /* ===================== INGRESOS ===================== */

        // 2) Órdenes (adelantos al crear la orden)
        $ordenes = \App\Models\Orden::with(['cliente','user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        $ordenItems = $ordenes->map(function ($o) {
            return [
                'id'          => $o->id,
                'hora'        => \Carbon\Carbon::parse($o->fecha_creacion)->format('H:i'),
                'descripcion' => "Orden {$o->numero} — ".($o->cliente->name ?? 'N/A'),
                'monto'       => (float) ($o->adelanto ?? 0),
                'usuario'     => $o->user->name ?? 'N/A',
            ];
        })->values();
        $totalOrdenes = (float) $ordenItems->sum('monto');

        // 3) Pagos de Órdenes (ACTIVO)
        $pagosOrdenes = \App\Models\OrdenPago::with(['orden.cliente','user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->whereHas('orden', fn($q) => $q->where('estado', '!=', 'Cancelada'))
            ->orderBy('created_at')
            ->get();

        $pagoOrdenItems = $pagosOrdenes->map(function ($p) {
            return [
                'id'          => $p->id,
                'hora'        => optional($p->created_at)->format('H:i') ?: '—',
                'descripcion' => "Pago orden {$p->orden->numero} — ".($p->orden->cliente->name ?? 'N/A'),
                'monto'       => (float) $p->monto,
                'usuario'     => $p->user->name ?? 'N/A',
                'metodo'      => $p->metodo ?? null, // EFECTIVO/QR
            ];
        })->values();
        $totalPagosOrdenes = (float) $pagoOrdenItems->sum('monto');

        // 4) Pagos de Préstamos (ACTIVO)
        $pagosPrestamos = \App\Models\PrestamoPago::with(['prestamo.cliente','user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->orderBy('created_at')
            ->get();

        $pagoPrestItems = $pagosPrestamos->map(function ($p) {
            return [
                'id'          => $p->id,
                'hora'        => optional($p->created_at)->format('H:i') ?: '—',
                'descripcion' => "Pago préstamo {$p->prestamo->numero} — ".($p->prestamo->cliente->name ?? 'N/A'),
                'monto'       => (float) $p->monto,
                'usuario'     => $p->user->name ?? 'N/A',
                'metodo'      => $p->metodo ?? null,      // EFECTIVO/QR…
                'tipo_pago'   => $p->tipo_pago ?? null,   // INTERES/SALDO (opcional)
            ];
        })->values();
        $totalPagosPrest = (float) $pagoPrestItems->sum('monto');

        // 4.b) Ingresos: Otros (manuales)
        $ingresosOtros = \App\Models\Ingreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $ingresoOtrosItems = $ingresosOtros->map(function($i){
            return [
                'id'          => $i->id,
                'hora'        => optional($i->created_at)->format('H:i') ?: '—',
                'descripcion' => $i->descripcion,
                'monto'       => (float) $i->monto,
                'usuario'     => $i->user->name ?? 'N/A',
                'metodo'      => $i->metodo ?? 'EFECTIVO',
                'estado'      => $i->estado,
            ];
        })->values();

        $totalIngresosOtros = (float) $ingresoOtrosItems
            ->filter(fn($x) => ($x['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        /* ===================== EGRESOS ===================== */

        // 5) Egresos por préstamos otorgados
        $prestamos = \App\Models\Prestamo::with(['cliente','user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->orderBy('fecha_creacion')
            ->get();

        $egresoItems = $prestamos->map(function ($pr) {
            return [
                'id'          => $pr->id,
                'hora'        => \Carbon\Carbon::parse($pr->fecha_creacion)->format('H:i'),
                'descripcion' => "Préstamo {$pr->numero} — ".($pr->cliente->name ?? 'N/A'),
                'monto'       => (float) ($pr->valor_prestado ?? 0),
                'usuario'     => $pr->user->name ?? 'N/A',
                'metodo'      => $pr->metodo_entrega ?? $pr->metodo ?? 'EFECTIVO',
                'estado'      => 'Activo',
            ];
        })->values();
        $totalEgresosPrest = (float) $egresoItems->sum('monto');

        // 6) Egresos: Otros (luz, agua, etc.)
        $egresosOtros = \App\Models\Egreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $egresoOtrosItems = $egresosOtros->map(function($e){
            return [
                'id'          => $e->id,
                'hora'        => optional($e->created_at)->format('H:i') ?: '—',
                'descripcion' => $e->descripcion,
                'monto'       => (float) $e->monto,
                'usuario'     => $e->user->name ?? 'N/A',
                'metodo'      => $e->metodo ?? 'EFECTIVO',
                'estado'      => $e->estado,
            ];
        })->values();

        $totalEgresosOtros = (float) $egresoOtrosItems
            ->filter(fn($x) => ($x['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        /* ===================== TOTALES ===================== */

        // Totales generales
        $ingresosSinCaja = $totalOrdenes + $totalPagosOrdenes + $totalPagosPrest + $totalIngresosOtros;
        $totalIngresos   = (float) $daily->opening_amount + $ingresosSinCaja;
        $totalEgresos    = $totalEgresosPrest + $totalEgresosOtros;
        $totalCaja       = (float) $daily->opening_amount + $ingresosSinCaja - $totalEgresos;

        // Totales por método (ingresos/egresos/neto)
        $sumMetodo = function ($items, $metodo) {
            return (float) collect($items)->filter(
                fn($x) => ($x['metodo'] ?? null) === $metodo && (($x['estado'] ?? 'Activo') === 'Activo')
            )->sum('monto');
        };

        $ingresosMetodo = [
            'EFECTIVO' =>
                $sumMetodo($pagoOrdenItems, 'EFECTIVO') +
                $sumMetodo($pagoPrestItems, 'EFECTIVO') +
                $sumMetodo($ingresoOtrosItems->all(), 'EFECTIVO'),
            'QR'       =>
                $sumMetodo($pagoOrdenItems, 'QR') +
                $sumMetodo($pagoPrestItems, 'QR') +
                $sumMetodo($ingresoOtrosItems->all(), 'QR'),
        ];
        $egresosMetodo = [
            'EFECTIVO' => $sumMetodo($egresoItems, 'EFECTIVO') + $sumMetodo($egresoOtrosItems->all(), 'EFECTIVO'),
            'QR'       => $sumMetodo($egresoItems, 'QR')       + $sumMetodo($egresoOtrosItems->all(), 'QR'),
        ];
        $netoMetodo = [
            'EFECTIVO' => $ingresosMetodo['EFECTIVO'] - $egresosMetodo['EFECTIVO'],
            'QR'       => $ingresosMetodo['QR']       - $egresosMetodo['QR'],
        ];

        return response()->json([
            'date'           => $date,
            'daily_cash'     => $daily,

            'ingresos'       => [
                'ordenes' => [
                    'total' => round($totalOrdenes, 2),
                    'items' => $ordenItems,
                ],
                'pagos_ordenes' => [
                    'total' => round($totalPagosOrdenes, 2),
                    'items' => $pagoOrdenItems,
                ],
                'pagos_prestamos' => [
                    'total' => round($totalPagosPrest, 2),
                    'items' => $pagoPrestItems,
                ],
                'otros' => [
                    'total' => round($totalIngresosOtros, 2),
                    'items' => $ingresoOtrosItems,
                ],
            ],

            'egresos'        => [
                'prestamos' => [
                    'total' => round($totalEgresosPrest, 2),
                    'items' => $egresoItems,
                ],
                'otros' => [
                    'total' => round($totalEgresosOtros, 2),
                    'items' => $egresoOtrosItems,
                ],
            ],

            'totales_metodo' => [
                'ingresos' => array_map(fn($v) => round($v, 2), $ingresosMetodo),
                'egresos'  => array_map(fn($v) => round($v, 2), $egresosMetodo),
                'neto'     => array_map(fn($v) => round($v, 2), $netoMetodo),
            ],

            // Totales globales
            'total_ingresos' => round($totalIngresos, 2),
            'total_egresos'  => round($totalEgresos, 2),
            'total_caja'     => round($totalCaja, 2),
        ]);
    }

    // Crea/actualiza caja inicial del día
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'opening_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $daily = DailyCash::updateOrCreate(
            ['date' => $data['date']],
            [
                'opening_amount' => $data['opening_amount'],
                'note' => $data['note'] ?? null,
                'user_id' => optional($request->user())->id,
            ]
        );

        return response()->json($daily->fresh());
    }
}
