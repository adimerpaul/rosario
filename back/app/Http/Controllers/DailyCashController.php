<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use App\Models\Orden;
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

        // 2) Ingresos por Órdenes (adelanto en la creación del día)
        $ordenes = Orden::with(['cliente','user'])
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

        // 3) Ingresos por Pagos de Órdenes (tabla orden_pagos) del día
        //    - Considera sólo pagos ACTIVO
        //    - (opcional) evita contar pagos de órdenes canceladas
        $pagos = \App\Models\OrdenPago::with(['orden.cliente','user'])
            ->whereDate('fecha', $date)                 // usa la fecha del pago
            ->where('estado', 'Activo')
            ->whereHas('orden', fn($q) => $q->where('estado', '!=', 'Cancelada'))
            ->orderBy('created_at')
            ->get();
//        error_log($pagos);

        $pagoItems = $pagos->map(function ($p) {
            return [
                'id'          => $p->id,
                // si tu campo "fecha" es sólo YYYY-MM-DD, usamos created_at para la hora
                'hora'        => optional($p->created_at)->format('H:i') ?: '—',
                'descripcion' => "Pago orden {$p->orden->numero} — ".($p->orden->cliente->name ?? 'N/A'),
                'monto'       => (float) $p->monto,
                'usuario'     => $p->user->name ?? 'N/A',
            ];
        })->values();

        $totalPagos = (float) $pagoItems->sum('monto');

        // 4) Totales
        $totalIngresos = (float) $daily->opening_amount + $totalOrdenes + $totalPagos;

        return response()->json([
            'date'          => $date,
            'daily_cash'    => $daily,
            'ingresos'      => [
                'ordenes' => [
                    'total' => round($totalOrdenes, 2),
                    'items' => $ordenItems,
                ],
                'pagos_ordenes' => [
                    'total' => round($totalPagos, 2),
                    'items' => $pagoItems,
                ],
            ],
            'total_ingresos' => round($totalIngresos, 2),
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
