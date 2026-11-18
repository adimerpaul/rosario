<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $date = $request->query('date', now()->toDateString());
        $days = (int) $request->query('days', 56); // recomendado 56 (8 semanas)
        if ($days < 7) {
            $days = 7;
        }
        if ($days > 120) {
            $days = 120;
        }

        // Si NO es Administrador, el dashboard se filtra por su user_id
        $userId = ($user && ($user->role ?? '') !== 'Administrador') ? $user->id : null;

        $end = Carbon::parse($date)->endOfDay();
        $start = Carbon::parse($date)->subDays($days - 1)->startOfDay();

        // =========================
        // 1) DAILY-CASH: series por día
        // =========================
        // Usamos tu daily-cash "derivado", pero sin llamar al endpoint para no hacer N requests.
        // Recalculamos totales por día desde tablas (ordenes, orden_pagos, prestamo_pagos, ingresos, prestamos, egresos)
        // y sumamos opening_amount de daily_cashes.
        //
        // Campos:
        // - ORDENES: fecha_creacion, adelanto, tipo_pago, estado != Cancelada
        // - ORDEN_PAGOS: fecha, monto, metodo, estado Activo
        // - PRESTAMO_PAGOS: fecha, monto, metodo, estado Activo
        // - INGRESOS: fecha, monto, metodo, estado Activo
        // - PRESTAMOS: fecha_creacion, valor_prestado, metodo_entrega/metodo  (egreso)
        // - EGRESOS: fecha, monto, metodo, estado Activo
        // - DAILY_CASHES: date, opening_amount

        // Opening por día
        $opening = DB::table('daily_cashes')
            ->selectRaw('DATE(date) as fecha, SUM(opening_amount) as opening_amount')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw('DATE(date)'))
            ->pluck('opening_amount', 'fecha')
            ->toArray();

        // ORDENES (adelanto)
        $ordenes = DB::table('ordenes')
            ->selectRaw('DATE(fecha_creacion) as fecha')
            ->selectRaw('SUM(COALESCE(adelanto,0)) as total')
            ->selectRaw('SUM(COALESCE(adelanto_qr,0)) as total_qr')
            ->selectRaw('SUM(COALESCE(adelanto_efectivo,0)) as total_efectivo')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$start->toDateString(), $end->toDateString()])
            ->where('estado', '!=', 'Cancelada')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw('DATE(fecha_creacion)'))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // ORDEN_PAGOS (ingreso)
        $ordenPagos = DB::table('orden_pagos')
            ->selectRaw('DATE(fecha) as fecha')
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw('DATE(fecha)'), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // PRESTAMO_PAGOS (ingreso)
        $prestamoPagos = DB::table('prestamo_pagos')
            ->selectRaw('DATE(fecha) as fecha')
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw('DATE(fecha)'), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // INGRESOS (manual)
        $ingresos = DB::table('ingresos')
            ->selectRaw('DATE(fecha) as fecha')
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw('DATE(fecha)'), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // PRESTAMOS (egreso)
        $prestamos = DB::table('prestamos')
            ->selectRaw('DATE(fecha_creacion) as fecha')
            ->selectRaw('SUM(COALESCE(valor_prestado,0)) as total')
//            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo_entrega, metodo, ''))='QR' THEN COALESCE(valor_prestado,0) ELSE 0 END) as total_qr")
//            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo_entrega, metodo, ''))='EFECTIVO' THEN COALESCE(valor_prestado,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$start->toDateString(), $end->toDateString()])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw('DATE(fecha_creacion)'))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // EGRESOS (manual)
        $egresos = DB::table('egresos')
            ->selectRaw('DATE(fecha) as fecha')
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw('DATE(fecha)'), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // Build días
        $daysArr = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();

            $open = (float) ($opening[$d] ?? 0);

            $o = $ordenes[$d] ?? null;
            $op = $ordenPagos[$d] ?? null;
            $pp = $prestamoPagos[$d] ?? null;
            $io = $ingresos[$d] ?? null;

            $pr = $prestamos[$d] ?? null;
            $eo = $egresos[$d] ?? null;

            $ing = (float) ($open
                + (float) ($o->total ?? 0)
                + (float) ($op->total ?? 0)
                + (float) ($pp->total ?? 0)
                + (float) ($io->total ?? 0)
            );

            $egr = (float) (
                (float) ($pr->total ?? 0)
                + (float) ($eo->total ?? 0)
            );

            $caja = $ing - $egr;

            // Totales ingresos por método (para donut del día)
            $ingEfe = (float) (
                (float) ($o->total_efectivo ?? 0) +
                (float) ($op->total_efectivo ?? 0) +
                (float) ($pp->total_efectivo ?? 0) +
                (float) ($io->total_efectivo ?? 0)
            );
            $ingQr = (float) (
                (float) ($o->total_qr ?? 0) +
                (float) ($op->total_qr ?? 0) +
                (float) ($pp->total_qr ?? 0) +
                (float) ($io->total_qr ?? 0)
            );

            $daysArr[] = [
                'date' => $d,
                'total_ingresos' => round($ing, 2),
                'total_egresos' => round($egr, 2),
                'total_caja' => round($caja, 2),
                'totales_metodo' => [
                    'ingresos' => [
                        'EFECTIVO' => round($ingEfe, 2),
                        'QR' => round($ingQr, 2),
                    ],
                ],
            ];

            $cursor->addDay();
        }

        // =========================
        // 2) KPIs del día seleccionado
        // =========================
        $todayPayload = collect($daysArr)->firstWhere('date', Carbon::parse($date)->toDateString());
        $todayPayload = $todayPayload ?: [
            'total_ingresos' => 0,
            'total_egresos' => 0,
            'total_caja' => 0,
            'totales_metodo' => ['ingresos' => ['EFECTIVO' => 0, 'QR' => 0]],
        ];

        // =========================
        // 3) Retrasos (solo contar, no traer todo)
        // =========================
        // Si tu lógica de retrasos es compleja en controllers, aquí solo devolvemos counts simples.
        // Ajusta si tus estados/campos son diferentes.
        $ordenesRetrasadas = DB::table('ordenes')
            ->where('estado', '!=', 'Cancelada')
            ->whereNotNull('fecha_entrega')
            ->whereDate('fecha_entrega', '<', Carbon::parse($date)->toDateString())
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->count();

        $prestamosRetrasados = DB::table('prestamos')
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', Carbon::parse($date)->toDateString())
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->count();

        // =========================
        // 4) Series (front ya las arma, pero igual mandamos listo)
        // =========================
        return response()->json([
            'meta' => [
                'date' => Carbon::parse($date)->toDateString(),
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $days,
                'filtered_user_id' => $userId,
            ],
            'kpi' => [
                'dayIngresos' => (float) $todayPayload['total_ingresos'],
                'dayEgresos' => (float) $todayPayload['total_egresos'],
                'dayCaja' => (float) $todayPayload['total_caja'],
                'ordenesRetrasadas' => (int) $ordenesRetrasadas,
                'prestamosRetrasados' => (int) $prestamosRetrasados,
            ],
            'donut' => [
                'labels' => ['EFECTIVO', 'QR'],
                'series' => [
                    (float) ($todayPayload['totales_metodo']['ingresos']['EFECTIVO'] ?? 0),
                    (float) ($todayPayload['totales_metodo']['ingresos']['QR'] ?? 0),
                ],
            ],
            'days' => $daysArr, // array de días con ingresos/egresos/caja + totales_metodo ingresos
        ]);
    }

    /**
     * Ventas de joyas (ordenes tipo "Venta directa", no canceladas) por periodo.
     * periodo: hoy | ayer | mes | anio | rango (con desde/hasta).
     * Agrupa por hora si es un solo dia, por dia hasta 62 dias y por mes si es mas largo.
     */
    public function ventas(Request $request)
    {
        $user = $request->user();
        $userId = ($user && ($user->role ?? '') !== 'Administrador') ? $user->id : null;

        $periodo = $request->query('periodo', 'hoy');
        $now = now();

        switch ($periodo) {
            case 'ayer':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'mes':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();
                break;
            case 'anio':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfDay();
                break;
            case 'rango':
                $start = Carbon::parse($request->query('desde', $now->toDateString()))->startOfDay();
                $end = Carbon::parse($request->query('hasta', $now->toDateString()))->endOfDay();
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                break;
            default:
                $periodo = 'hoy';
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
        }

        $dias = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $granularidad = $dias <= 1 ? 'hora' : ($dias <= 62 ? 'dia' : 'mes');

        // Periodo anterior de igual duracion (para comparar)
        $prevEnd = $start->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subDays($dias - 1)->startOfDay();
        if ($periodo === 'mes') {
            $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
            $prevEnd = $prevStart->copy()->addDays($dias - 1)->endOfDay();
        } elseif ($periodo === 'anio') {
            $prevStart = $start->copy()->subYear()->startOfYear();
            $prevEnd = $prevStart->copy()->addDays($dias - 1)->endOfDay();
        }

        $base = fn (Carbon $desde, Carbon $hasta, string $tipo = 'Venta directa') => DB::table('ordenes')
            ->whereNull('ordenes.deleted_at')
            ->where('ordenes.tipo', $tipo)
            ->where('ordenes.estado', '!=', 'Cancelada')
            ->whereBetween('ordenes.fecha_creacion', [$desde, $hasta])
            ->when($userId, fn ($q) => $q->where('ordenes.user_id', $userId));

        $resumen = fn (Carbon $desde, Carbon $hasta, string $tipo = 'Venta directa') => $base($desde, $hasta, $tipo)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(costo_total),0) as monto')
            ->selectRaw('COALESCE(SUM(adelanto_efectivo),0) as efectivo, COALESCE(SUM(adelanto_qr),0) as qr')
            ->first();

        $actual = $resumen($start, $end);
        $anterior = $resumen($prevStart, $prevEnd);
        $ordenesTrabajo = $resumen($start, $end, 'Orden');

        // Serie agrupada
        $bucketSql = match ($granularidad) {
            'hora' => 'HOUR(fecha_creacion)',
            'dia' => 'DATE(fecha_creacion)',
            default => "DATE_FORMAT(fecha_creacion, '%Y-%m')",
        };
        $rows = $base($start, $end)
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as cantidad, COALESCE(SUM(costo_total),0) as monto")
            ->groupBy(DB::raw($bucketSql))
            ->get()
            ->keyBy(fn ($r) => (string) $r->bucket);

        $serie = [];
        if ($granularidad === 'hora') {
            // Rango de horas con movimiento, minimo horario comercial 8-20
            $horas = $rows->keys()->map(fn ($h) => (int) $h);
            $desdeH = min(8, $horas->min() ?? 8);
            $hastaH = max(20, $horas->max() ?? 20);
            for ($h = $desdeH; $h <= $hastaH; $h++) {
                $r = $rows->get((string) $h);
                $serie[] = ['label' => sprintf('%02d:00', $h), 'key' => (string) $h, 'monto' => round((float) ($r->monto ?? 0), 2), 'cantidad' => (int) ($r->cantidad ?? 0)];
            }
        } elseif ($granularidad === 'dia') {
            for ($c = $start->copy()->startOfDay(); $c->lte($end); $c->addDay()) {
                $k = $c->toDateString();
                $r = $rows->get($k);
                $serie[] = ['label' => $c->format('d/m'), 'key' => $k, 'monto' => round((float) ($r->monto ?? 0), 2), 'cantidad' => (int) ($r->cantidad ?? 0)];
            }
        } else {
            $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            for ($c = $start->copy()->startOfMonth(); $c->lte($end); $c->addMonthNoOverflow()) {
                $k = $c->format('Y-m');
                $r = $rows->get($k);
                $serie[] = ['label' => $meses[$c->month - 1].' '.$c->format('y'), 'key' => $k, 'monto' => round((float) ($r->monto ?? 0), 2), 'cantidad' => (int) ($r->cantidad ?? 0)];
            }
        }

        // Ranking de vendedores
        $vendedores = $base($start, $end)
            ->leftJoin('users', 'users.id', '=', 'ordenes.user_id')
            ->selectRaw("ordenes.user_id, COALESCE(users.name, users.username, 'Sin usuario') as nombre")
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(ordenes.costo_total),0) as monto')
            ->groupBy('ordenes.user_id', 'users.name', 'users.username')
            ->orderByDesc('monto')
            ->limit(8)
            ->get()
            ->map(fn ($v) => [
                'user_id' => $v->user_id,
                'nombre' => $v->nombre,
                'cantidad' => (int) $v->cantidad,
                'monto' => round((float) $v->monto, 2),
            ]);

        $monto = round((float) $actual->monto, 2);
        $cantidad = (int) $actual->cantidad;
        $montoPrev = round((float) $anterior->monto, 2);

        return response()->json([
            'periodo' => $periodo,
            'desde' => $start->toDateString(),
            'hasta' => $end->toDateString(),
            'granularidad' => $granularidad,
            'filtered_user_id' => $userId,
            'kpi' => [
                'monto' => $monto,
                'cantidad' => $cantidad,
                'ticket' => $cantidad > 0 ? round($monto / $cantidad, 2) : 0,
                'monto_anterior' => $montoPrev,
                'cantidad_anterior' => (int) $anterior->cantidad,
                'variacion' => $montoPrev > 0 ? round((($monto - $montoPrev) / $montoPrev) * 100, 1) : null,
                'cobrado_efectivo' => round((float) $actual->efectivo, 2),
                'cobrado_qr' => round((float) $actual->qr, 2),
                'ordenes_cantidad' => (int) $ordenesTrabajo->cantidad,
                'ordenes_monto' => round((float) $ordenesTrabajo->monto, 2),
            ],
            'serie' => $serie,
            'vendedores' => $vendedores,
        ]);
    }

    public function reportes(Request $request)
    {
        $user = $request->user();

        $dateFrom = $request->query('date_from', now()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        // users[]=1&users[]=2 (opcional)
        $userIds = $request->query('users', []);
        if (! is_array($userIds)) {
            $userIds = [$userIds];
        }
        $userIds = array_values(array_filter(array_map('intval', $userIds)));

        // Si NO es Administrador, fuerza su user_id
        $onlyUserId = ($user && ($user->role ?? '') !== 'Administrador') ? (int) $user->id : null;
        $filterUserIds = $onlyUserId ? [$onlyUserId] : $userIds;

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        // 1) Lista usuarios para el filtro
        $users = DB::table('users')
            ->select('id', 'name')
            ->when($onlyUserId, fn ($q) => $q->where('id', $onlyUserId))
            ->orderBy('name')
            ->get();

        // Helpers para normalizar método de pago
        $normalize = "CASE
        WHEN UPPER(TRIM(COALESCE(%s,''))) IN ('QR','Q.R.','Q R') THEN 'QR'
        WHEN UPPER(TRIM(COALESCE(%s,''))) IN ('EFECTIVO','EFECTIVO ') THEN 'EFECTIVO'
        WHEN TRIM(COALESCE(%s,'')) = '' THEN 'EFECTIVO'
        ELSE UPPER(TRIM(COALESCE(%s,'')))
    END";

        // 2) Totales (rápido)
        $sumOrdenesAdelanto = DB::table('ordenes')
            ->where('estado', '!=', 'Cancelada')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(adelanto,0)'));

        $sumOrdenPagos = DB::table('orden_pagos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumPrestamoPagos = DB::table('prestamo_pagos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumIngresos = DB::table('ingresos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumEgresos = DB::table('egresos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumPrestamos = DB::table('prestamos')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(valor_prestado,0)'));

        $totalIngresos = (float) $sumOrdenesAdelanto + (float) $sumOrdenPagos + (float) $sumPrestamoPagos + (float) $sumIngresos;
        $totalEgresos = (float) $sumEgresos + (float) $sumPrestamos;
        $totalNeto = $totalIngresos - $totalEgresos;

        // 3) Serie diaria (una línea)
        $ingOrd = DB::table('ordenes')
            ->selectRaw('DATE(fecha_creacion) as fecha, SUM(COALESCE(adelanto,0)) as total')
            ->where('estado', '!=', 'Cancelada')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw('DATE(fecha_creacion)'))
            ->pluck('total', 'fecha')->toArray();

        $ingOP = DB::table('orden_pagos')
            ->selectRaw('DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->pluck('total', 'fecha')->toArray();

        $ingPP = DB::table('prestamo_pagos')
            ->selectRaw('DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->pluck('total', 'fecha')->toArray();

        $ingMan = DB::table('ingresos')
            ->selectRaw('DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->pluck('total', 'fecha')->toArray();

        $egrMan = DB::table('egresos')
            ->selectRaw('DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw('DATE(fecha)'))
            ->pluck('total', 'fecha')->toArray();

        $egrPres = DB::table('prestamos')
            ->selectRaw('DATE(fecha_creacion) as fecha, SUM(COALESCE(valor_prestado,0)) as total')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw('DATE(fecha_creacion)'))
            ->pluck('total', 'fecha')->toArray();

        $days = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $d = $cursor->toDateString();

            $ing = (float) ($ingOrd[$d] ?? 0) + (float) ($ingOP[$d] ?? 0) + (float) ($ingPP[$d] ?? 0) + (float) ($ingMan[$d] ?? 0);
            $egr = (float) ($egrMan[$d] ?? 0) + (float) ($egrPres[$d] ?? 0);

            $days[] = [
                'date' => $d,
                'ingresos' => round($ing, 2),
                'egresos' => round($egr, 2),
                'neto' => round($ing - $egr, 2),
            ];

            $cursor->addDay();
        }

        // 4) Métodos de pago (solo ingresos)
        $metodos = [];

        // Adelantos de ordenes: desglosados en efectivo y QR
        $mpOrdenesTotales = DB::table('ordenes')
            ->selectRaw('SUM(COALESCE(adelanto_efectivo,0)) as efectivo')
            ->selectRaw('SUM(COALESCE(adelanto_qr,0)) as qr')
            ->where('estado', '!=', 'Cancelada')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->first();

        $mpOrdenes = collect([
            (object) ['metodo' => 'EFECTIVO', 'total' => (float) ($mpOrdenesTotales->efectivo ?? 0)],
            (object) ['metodo' => 'QR', 'total' => (float) ($mpOrdenesTotales->qr ?? 0)],
        ])->filter(fn ($r) => $r->total > 0);

        $mpOP = DB::table('orden_pagos')
            ->selectRaw(sprintf("$normalize as metodo", 'metodo', 'metodo', 'metodo', 'metodo'))
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        $mpPP = DB::table('prestamo_pagos')
            ->selectRaw(sprintf("$normalize as metodo", 'metodo', 'metodo', 'metodo', 'metodo'))
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        $mpIng = DB::table('ingresos')
            ->selectRaw(sprintf("$normalize as metodo", 'metodo', 'metodo', 'metodo', 'metodo'))
            ->selectRaw('SUM(COALESCE(monto,0)) as total')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        foreach ([$mpOrdenes, $mpOP, $mpPP, $mpIng] as $rows) {
            foreach ($rows as $r) {
                $k = $r->metodo ?: 'EFECTIVO';
                $metodos[$k] = ($metodos[$k] ?? 0) + (float) $r->total;
            }
        }

        // ordenado desc
        arsort($metodos);

        $metodosArr = [];
        foreach ($metodos as $k => $v) {
            $metodosArr[] = ['metodo' => $k, 'total' => round($v, 2)];
        }

        // 5) Ingresos por usuario (bar)
        $ingUser = DB::table('users as u')
            ->leftJoin('ordenes as o', function ($j) use ($from, $to) {
                $j->on('u.id', '=', 'o.user_id')
                    ->where('o.estado', '!=', 'Cancelada')
                    ->whereBetween(DB::raw('DATE(o.fecha_creacion)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->leftJoin('orden_pagos as op', function ($j) use ($from, $to) {
                $j->on('u.id', '=', 'op.user_id')
                    ->where('op.estado', '=', 'Activo')
                    ->whereBetween(DB::raw('DATE(op.fecha)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->leftJoin('prestamo_pagos as pp', function ($j) use ($from, $to) {
                $j->on('u.id', '=', 'pp.user_id')
                    ->where('pp.estado', '=', 'Activo')
                    ->whereBetween(DB::raw('DATE(pp.fecha)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->leftJoin('ingresos as i', function ($j) use ($from, $to) {
                $j->on('u.id', '=', 'i.user_id')
                    ->where('i.estado', '=', 'Activo')
                    ->whereBetween(DB::raw('DATE(i.fecha)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->selectRaw('u.id as user_id, u.name as user_name')
            ->selectRaw('SUM(COALESCE(o.adelanto,0)) + SUM(COALESCE(op.monto,0)) + SUM(COALESCE(pp.monto,0)) + SUM(COALESCE(i.monto,0)) as total')
            ->when(count($filterUserIds), fn ($q) => $q->whereIn('u.id', $filterUserIds))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'meta' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'filtered_user_ids' => $filterUserIds,
            ],
            'users' => $users,
            'totals' => [
                'ingresos' => round($totalIngresos, 2),
                'egresos' => round($totalEgresos, 2),
                'neto' => round($totalNeto, 2),
            ],
            'days' => $days,
            'metodos_pago' => $metodosArr,
            'ingresos_por_usuario' => $ingUser,
        ]);
    }
}
