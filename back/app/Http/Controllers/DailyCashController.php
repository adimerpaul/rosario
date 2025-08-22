<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use Illuminate\Http\Request;

class DailyCashController extends Controller{
    public function show(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        // 1) Caja del día
        $daily = DailyCash::firstOrCreate(
            ['date' => $date],
            ['opening_amount' => 0, 'user_id' => optional($request->user())->id]
        );

        /* ===================== INGRESOS ===================== */

        // 2) Órdenes (adelantos al crear la orden)
        $ordenes = \App\Models\Orden::with(['cliente','user'])
            ->whereDate('fecha_creacion', $date)
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

        /* ===================== EGRESOS ===================== */

        // 5) Egresos por préstamos otorgados (salida de dinero el día de creación)
        $prestamos = \App\Models\Prestamo::with(['cliente','user'])
            ->whereDate('fecha_creacion', $date)
            ->orderBy('fecha_creacion')
            ->get();

        $egresoItems = $prestamos->map(function ($pr) {
            return [
                'id'          => $pr->id,
                'hora'        => \Carbon\Carbon::parse($pr->fecha_creacion)->format('H:i'),
                'descripcion' => "Préstamo {$pr->numero} — ".($pr->cliente->name ?? 'N/A'),
                'monto'       => (float) ($pr->valor_prestado ?? 0),
                'usuario'     => $pr->user->name ?? 'N/A',
                // Si no tienes columna de método en préstamos, caerá en 'EFECTIVO'
                'metodo'      => $pr->metodo_entrega ?? $pr->metodo ?? 'EFECTIVO',
            ];
        })->values();
        $totalEgresosPrest = (float) $egresoItems->sum('monto');

        /* ===================== TOTALES ===================== */

        // Totales generales
        $ingresosSinCaja = $totalOrdenes + $totalPagosOrdenes + $totalPagosPrest;
        $totalIngresos   = (float) $daily->opening_amount + $ingresosSinCaja;           // mantiene compatibilidad
        $totalEgresos    = $totalEgresosPrest;
        $totalCaja       = (float) $daily->opening_amount + $ingresosSinCaja - $totalEgresos;

        // Totales por método (ingresos/egresos/neto)
        $sumMetodo = function ($items, $metodo) {
            return (float) collect($items)->filter(fn($x) => ($x['metodo'] ?? null) === $metodo)->sum('monto');
        };

        $ingresosMetodo = [
            'EFECTIVO' => $sumMetodo($pagoOrdenItems, 'EFECTIVO') + $sumMetodo($pagoPrestItems, 'EFECTIVO'),
            'QR'       => $sumMetodo($pagoOrdenItems, 'QR')       + $sumMetodo($pagoPrestItems, 'QR'),
        ];
        $egresosMetodo = [
            'EFECTIVO' => $sumMetodo($egresoItems, 'EFECTIVO'),
            'QR'       => $sumMetodo($egresoItems, 'QR'),
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
            ],

            'egresos'        => [
                'prestamos' => [
                    'total' => round($totalEgresosPrest, 2),
                    'items' => $egresoItems,
                ],
            ],

            'totales_metodo' => [
                'ingresos' => array_map(fn($v) => round($v, 2), $ingresosMetodo),
                'egresos'  => array_map(fn($v) => round($v, 2), $egresosMetodo),
                'neto'     => array_map(fn($v) => round($v, 2), $netoMetodo),
            ],

            // Totales globales
            'total_ingresos' => round($totalIngresos, 2), // = caja inicial + ingresos (NO descuenta egresos)
            'total_egresos'  => round($totalEgresos, 2),
            'total_caja'     => round($totalCaja, 2),     // = caja inicial + ingresos - egresos
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
