<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use App\Models\Orden;
use Illuminate\Http\Request;

class DailyCashController extends Controller{
    public function show(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        // Crea registro si no existe (caja inicial 0)
        $daily = DailyCash::firstOrCreate(
            ['date' => $date],
            ['opening_amount' => 0, 'user_id' => optional($request->user())->id]
        );

        // Órdenes del día (excluye canceladas)
        $ordenes = Orden::with(['cliente','user'])
            ->whereDate('fecha_creacion', $date)
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        // Items: se considera ingreso el "adelanto" de la orden creada ese día
        $items = $ordenes->map(function ($o) {
            return [
                'id' => $o->id,
                'hora' => \Carbon\Carbon::parse($o->fecha_creacion)->format('H:i'),
                'descripcion' => "Orden {$o->numero} — ".($o->cliente->name ?? 'N/A'),
                'monto' => (float) ($o->adelanto ?? 0),
                'usuario' => $o->user->name ?? 'N/A',
            ];
        });

        $totalOrdenes = $items->sum('monto');
        $totalIngresos = (float) $daily->opening_amount + $totalOrdenes;

        return response()->json([
            'date' => $date,
            'daily_cash' => $daily,
            'ingresos' => [
                'ordenes' => [
                    'total' => round($totalOrdenes,2),
                    'items' => $items,
                ],
            ],
            'total_ingresos' => round($totalIngresos,2),
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
