<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyCashController extends Controller
{
    public function show(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        // filtros opcionales
        $metodo_pago = $request->query('metodo_pago'); // EFECTIVO | QR | null
        $username    = $request->query('usuario');     // username | null

        // user efectivo (si no es admin, solo él)
        $auth = $request->user();
        $user = null;

        if ($auth && ($auth->role ?? '') !== 'Administrador') {
            $user = $auth;
        } else {
            if ($username) {
                $user = User::where('username', $username)->first();
                if (!$user) {
                    // usuario no existe -> estructura vacía
                    $daily = DailyCash::firstOrCreate(
                        ['date' => $date],
                        ['opening_amount' => 0, 'user_id' => optional($auth)->id]
                    );

                    return response()->json([
                        'date' => $date,
                        'daily_cash' => $daily,
                        'suggested_opening_amount' => 0,
                        'items_ingresos' => [],
                        'items_egresos' => [],
                        'total_ingresos' => (float)$daily->opening_amount,
                        'total_egresos' => 0.0,
                        'total_caja' => (float)$daily->opening_amount,
                    ]);
                }
            }
        }

        // Caja del día
        $daily = DailyCash::firstOrCreate(
            ['date' => $date],
            ['opening_amount' => 0, 'user_id' => optional($auth)->id]
        );

        // ✅ Sugerido (ayer) en el mismo response (SIN otro GET en frontend)
        $yesterday = Carbon::parse($date)->subDay()->toDateString();
        $suggested = (float) (DailyCash::where('date', $yesterday)->value('opening_amount') ?? 0);

        // normalizar método
        $normMetodo = function ($m) {
            $m = strtoupper(trim((string)$m));
            if ($m === 'CASH') return 'EFECTIVO';
            if ($m === '') return null;
            return $m;
        };

        // helper: aplica filtro metodo si viene
        $passMetodo = function ($m) use ($metodo_pago, $normMetodo) {
            if (!$metodo_pago) return true;
            return $normMetodo($m) === $normMetodo($metodo_pago);
        };

        /* ===================== INGRESOS (planos) ===================== */

        // 1) Órdenes (adelanto)
        $ordenes = \App\Models\Orden::with(['cliente','user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $ordenes->map(function ($o) use ($normMetodo) {
            return [
                'id'          => $o->id,
                'hora'        => Carbon::parse($o->fecha_creacion)->format('H:i'),
                'descripcion' => "Orden {$o->numero} — ".($o->cliente->name ?? 'N/A'),
                'monto'       => (float) ($o->adelanto ?? 0),
                'usuario'     => $o->user->name ?? 'N/A',
                'metodo'      => $normMetodo($o->tipo_pago),
                'fuente'      => 'ORDEN',
                'estado'      => 'Activo',
                'key'         => "o-{$o->id}",
            ];
        })->filter(fn($x) => $x['monto'] > 0)->values();

        // 2) Pagos de órdenes
        $pagosOrdenes = \App\Models\OrdenPago::with(['orden.cliente','user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->whereHas('orden', fn($q) => $q->where('estado', '!=', 'Cancelada'))
            ->orderBy('created_at')
            ->get();

        $itemsPagoOrden = $pagosOrdenes->map(function ($p) use ($normMetodo) {
            return [
                'id'          => $p->id,
                'hora'        => optional($p->created_at)->format('H:i') ?: '—',
                'descripcion' => "Pago orden {$p->orden->numero} — ".($p->orden->cliente->name ?? 'N/A'),
                'monto'       => (float) $p->monto,
                'usuario'     => $p->user->name ?? 'N/A',
                'metodo'      => $normMetodo($p->metodo),
                'fuente'      => 'PAGO ORDEN',
                'estado'      => $p->estado ?? 'Activo',
                'key'         => "po-{$p->id}",
            ];
        })->values();

        // 3) Pagos de préstamos
        $pagosPrestamos = \App\Models\PrestamoPago::with(['prestamo.cliente','user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->orderBy('created_at')
            ->get();

        $itemsPagoPrest = $pagosPrestamos->map(function ($p) use ($normMetodo) {
            return [
                'id'          => $p->id,
                'hora'        => optional($p->created_at)->format('H:i') ?: '—',
                'descripcion' => "Pago préstamo {$p->prestamo->numero} — ".($p->prestamo->cliente->name ?? 'N/A'),
                'monto'       => (float) $p->monto,
                'usuario'     => $p->user->name ?? 'N/A',
                'metodo'      => $normMetodo($p->metodo),
                'fuente'      => 'PAGO PRÉSTAMO',
                'estado'      => $p->estado ?? 'Activo',
                'key'         => "pp-{$p->id}",
            ];
        })->values();

        // 4) Ingresos manuales
        $ingresosOtros = \App\Models\Ingreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $itemsIngresoOtros = $ingresosOtros->map(function ($i) use ($normMetodo) {
            return [
                'id'          => $i->id,
                'hora'        => optional($i->created_at)->format('H:i') ?: '—',
                'descripcion' => $i->descripcion,
                'monto'       => (float) $i->monto,
                'usuario'     => $i->user->name ?? 'N/A',
                'metodo'      => $normMetodo($i->metodo ?? 'EFECTIVO'),
                'fuente'      => 'INGRESO',
                'estado'      => $i->estado ?? 'Activo',
                'key'         => "in-{$i->id}",
            ];
        })->values();

        // juntar ingresos
        $items_ingresos = collect()
            ->merge($itemsOrden)
            ->merge($itemsPagoOrden)
            ->merge($itemsPagoPrest)
            ->merge($itemsIngresoOtros)
            ->filter(fn($x) => $passMetodo($x['metodo'] ?? null)) // filtro por método si viene
            ->values();

        /* ===================== EGRESOS (planos) ===================== */

        // 1) Préstamos otorgados (egreso)
        $prestamos = \App\Models\Prestamo::with(['cliente','user'])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $prestamos->map(function ($pr) use ($normMetodo) {
            return [
                'id'          => $pr->id,
                'hora'        => Carbon::parse($pr->fecha_creacion)->format('H:i'),
                'descripcion' => "Préstamo {$pr->numero} — ".($pr->cliente->name ?? 'N/A'),
                'monto'       => (float) ($pr->valor_prestado ?? 0),
                'usuario'     => $pr->user->name ?? 'N/A',
                'metodo'      => $normMetodo($pr->metodo_entrega ?? $pr->metodo ?? 'EFECTIVO'),
                'fuente'      => 'PRÉSTAMO OTORGADO',
                'estado'      => 'Activo',
                'key'         => "pr-{$pr->id}",
            ];
        })->filter(fn($x) => $x['monto'] > 0)->values();

        // 2) Egresos manuales
        $egresosOtros = \App\Models\Egreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $itemsEgresoOtros = $egresosOtros->map(function ($e) use ($normMetodo) {
            return [
                'id'          => $e->id,
                'hora'        => optional($e->created_at)->format('H:i') ?: '—',
                'descripcion' => $e->descripcion,
                'monto'       => (float) $e->monto,
                'usuario'     => $e->user->name ?? 'N/A',
                'metodo'      => $normMetodo($e->metodo ?? 'EFECTIVO'),
                'fuente'      => 'EGRESO',
                'estado'      => $e->estado ?? 'Activo',
                'key'         => "eg-{$e->id}",
            ];
        })->values();

        $items_egresos = collect()
            ->merge($itemsPrestamos)
            ->merge($itemsEgresoOtros)
            ->filter(fn($x) => $passMetodo($x['metodo'] ?? null))
            ->values();

        /* ===================== TOTALES ===================== */

        $sumActivos = fn($items) => (float) collect($items)
            ->filter(fn($x) => ($x['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        $ingresosSinCaja = $sumActivos($items_ingresos);
        $egresosTotal    = $sumActivos($items_egresos);

        $total_ingresos = (float)$daily->opening_amount + $ingresosSinCaja;
        $total_egresos  = (float)$egresosTotal;
        $total_caja     = (float)$daily->opening_amount + $ingresosSinCaja - $egresosTotal;

        return response()->json([
            'date' => $date,
            'daily_cash' => $daily,
            'suggested_opening_amount' => $suggested,

            'items_ingresos' => $items_ingresos,
            'items_egresos'  => $items_egresos,

            'total_ingresos' => round($total_ingresos, 2),
            'total_egresos'  => round($total_egresos, 2),
            'total_caja'     => round($total_caja, 2),
        ]);
    }

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
