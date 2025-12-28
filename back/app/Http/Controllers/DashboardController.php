<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $date = $request->query('date', now()->toDateString());
        $days = (int) $request->query('days', 56); // recomendado 56 (8 semanas)
        if ($days < 7) $days = 7;
        if ($days > 120) $days = 120;

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
            ->selectRaw("DATE(date) as fecha, SUM(opening_amount) as opening_amount")
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw("DATE(date)"))
            ->pluck('opening_amount', 'fecha')
            ->toArray();

        // ORDENES (adelanto)
        $ordenes = DB::table('ordenes')
            ->selectRaw("DATE(fecha_creacion) as fecha")
            ->selectRaw("SUM(COALESCE(adelanto,0)) as total")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(tipo_pago,''))='QR' THEN COALESCE(adelanto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(tipo_pago,''))='EFECTIVO' OR COALESCE(tipo_pago,'')='Efectivo' THEN COALESCE(adelanto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw("DATE(fecha_creacion)"), [$start->toDateString(), $end->toDateString()])
            ->where('estado', '!=', 'Cancelada')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw("DATE(fecha_creacion)"))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // ORDEN_PAGOS (ingreso)
        $ordenPagos = DB::table('orden_pagos')
            ->selectRaw("DATE(fecha) as fecha")
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw("DATE(fecha)"), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // PRESTAMO_PAGOS (ingreso)
        $prestamoPagos = DB::table('prestamo_pagos')
            ->selectRaw("DATE(fecha) as fecha")
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw("DATE(fecha)"), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // INGRESOS (manual)
        $ingresos = DB::table('ingresos')
            ->selectRaw("DATE(fecha) as fecha")
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw("DATE(fecha)"), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // PRESTAMOS (egreso)
        $prestamos = DB::table('prestamos')
            ->selectRaw("DATE(fecha_creacion) as fecha")
            ->selectRaw("SUM(COALESCE(valor_prestado,0)) as total")
//            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo_entrega, metodo, ''))='QR' THEN COALESCE(valor_prestado,0) ELSE 0 END) as total_qr")
//            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo_entrega, metodo, ''))='EFECTIVO' THEN COALESCE(valor_prestado,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw("DATE(fecha_creacion)"), [$start->toDateString(), $end->toDateString()])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw("DATE(fecha_creacion)"))
            ->get()
            ->keyBy('fecha')
            ->toArray();

        // EGRESOS (manual)
        $egresos = DB::table('egresos')
            ->selectRaw("DATE(fecha) as fecha")
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='QR' THEN COALESCE(monto,0) ELSE 0 END) as total_qr")
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(metodo,''))='EFECTIVO' THEN COALESCE(monto,0) ELSE 0 END) as total_efectivo")
            ->whereBetween(DB::raw("DATE(fecha)"), [$start->toDateString(), $end->toDateString()])
            ->where('estado', 'Activo')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->groupBy(DB::raw("DATE(fecha)"))
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
                'total_egresos'  => round($egr, 2),
                'total_caja'     => round($caja, 2),
                'totales_metodo' => [
                    'ingresos' => [
                        'EFECTIVO' => round($ingEfe, 2),
                        'QR'       => round($ingQr, 2),
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
            'total_egresos'  => 0,
            'total_caja'     => 0,
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
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->count();

        $prestamosRetrasados = DB::table('prestamos')
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', Carbon::parse($date)->toDateString())
            ->when($userId, fn($q) => $q->where('user_id', $userId))
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
                'dayEgresos'  => (float) $todayPayload['total_egresos'],
                'dayCaja'     => (float) $todayPayload['total_caja'],
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
    public function reportes(Request $request)
    {
        $user = $request->user();

        $dateFrom = $request->query('date_from', now()->toDateString());
        $dateTo   = $request->query('date_to', now()->toDateString());

        // users[]=1&users[]=2 (opcional)
        $userIds = $request->query('users', []);
        if (!is_array($userIds)) $userIds = [$userIds];
        $userIds = array_values(array_filter(array_map('intval', $userIds)));

        // Si NO es Administrador, fuerza su user_id
        $onlyUserId = ($user && ($user->role ?? '') !== 'Administrador') ? (int) $user->id : null;
        $filterUserIds = $onlyUserId ? [$onlyUserId] : $userIds;

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->endOfDay();

        // 1) Lista usuarios para el filtro
        $users = DB::table('users')
            ->select('id', 'name')
            ->when($onlyUserId, fn($q) => $q->where('id', $onlyUserId))
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
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(adelanto,0)'));

        $sumOrdenPagos = DB::table('orden_pagos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumPrestamoPagos = DB::table('prestamo_pagos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumIngresos = DB::table('ingresos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumEgresos = DB::table('egresos')
            ->where('estado', 'Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(monto,0)'));

        $sumPrestamos = DB::table('prestamos')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->sum(DB::raw('COALESCE(valor_prestado,0)'));

        $totalIngresos = (float)$sumOrdenesAdelanto + (float)$sumOrdenPagos + (float)$sumPrestamoPagos + (float)$sumIngresos;
        $totalEgresos  = (float)$sumEgresos + (float)$sumPrestamos;
        $totalNeto     = $totalIngresos - $totalEgresos;

        // 3) Serie diaria (una línea)
        $ingOrd = DB::table('ordenes')
            ->selectRaw("DATE(fecha_creacion) as fecha, SUM(COALESCE(adelanto,0)) as total")
            ->where('estado','!=','Cancelada')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw("DATE(fecha_creacion)"))
            ->pluck('total','fecha')->toArray();

        $ingOP = DB::table('orden_pagos')
            ->selectRaw("DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->pluck('total','fecha')->toArray();

        $ingPP = DB::table('prestamo_pagos')
            ->selectRaw("DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->pluck('total','fecha')->toArray();

        $ingMan = DB::table('ingresos')
            ->selectRaw("DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->pluck('total','fecha')->toArray();

        $egrMan = DB::table('egresos')
            ->selectRaw("DATE(fecha) as fecha, SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw("DATE(fecha)"))
            ->pluck('total','fecha')->toArray();

        $egrPres = DB::table('prestamos')
            ->selectRaw("DATE(fecha_creacion) as fecha, SUM(COALESCE(valor_prestado,0)) as total")
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy(DB::raw("DATE(fecha_creacion)"))
            ->pluck('total','fecha')->toArray();

        $days = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $d = $cursor->toDateString();

            $ing = (float)($ingOrd[$d] ?? 0) + (float)($ingOP[$d] ?? 0) + (float)($ingPP[$d] ?? 0) + (float)($ingMan[$d] ?? 0);
            $egr = (float)($egrMan[$d] ?? 0) + (float)($egrPres[$d] ?? 0);

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

        $mpOrdenes = DB::table('ordenes')
            ->selectRaw(sprintf("$normalize as metodo", 'tipo_pago','tipo_pago','tipo_pago','tipo_pago'))
            ->selectRaw("SUM(COALESCE(adelanto,0)) as total")
            ->where('estado','!=','Cancelada')
            ->whereBetween(DB::raw('DATE(fecha_creacion)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        $mpOP = DB::table('orden_pagos')
            ->selectRaw(sprintf("$normalize as metodo", 'metodo','metodo','metodo','metodo'))
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        $mpPP = DB::table('prestamo_pagos')
            ->selectRaw(sprintf("$normalize as metodo", 'metodo','metodo','metodo','metodo'))
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        $mpIng = DB::table('ingresos')
            ->selectRaw(sprintf("$normalize as metodo", 'metodo','metodo','metodo','metodo'))
            ->selectRaw("SUM(COALESCE(monto,0)) as total")
            ->where('estado','Activo')
            ->whereBetween(DB::raw('DATE(fecha)'), [$from->toDateString(), $to->toDateString()])
            ->when(count($filterUserIds), fn($q) => $q->whereIn('user_id', $filterUserIds))
            ->groupBy('metodo')
            ->get();

        foreach ([$mpOrdenes, $mpOP, $mpPP, $mpIng] as $rows) {
            foreach ($rows as $r) {
                $k = $r->metodo ?: 'EFECTIVO';
                $metodos[$k] = ($metodos[$k] ?? 0) + (float)$r->total;
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
            ->leftJoin('ordenes as o', function($j) use ($from,$to) {
                $j->on('u.id','=','o.user_id')
                    ->where('o.estado','!=','Cancelada')
                    ->whereBetween(DB::raw('DATE(o.fecha_creacion)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->leftJoin('orden_pagos as op', function($j) use ($from,$to) {
                $j->on('u.id','=','op.user_id')
                    ->where('op.estado','=','Activo')
                    ->whereBetween(DB::raw('DATE(op.fecha)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->leftJoin('prestamo_pagos as pp', function($j) use ($from,$to) {
                $j->on('u.id','=','pp.user_id')
                    ->where('pp.estado','=','Activo')
                    ->whereBetween(DB::raw('DATE(pp.fecha)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->leftJoin('ingresos as i', function($j) use ($from,$to) {
                $j->on('u.id','=','i.user_id')
                    ->where('i.estado','=','Activo')
                    ->whereBetween(DB::raw('DATE(i.fecha)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->selectRaw("u.id as user_id, u.name as user_name")
            ->selectRaw("SUM(COALESCE(o.adelanto,0)) + SUM(COALESCE(op.monto,0)) + SUM(COALESCE(pp.monto,0)) + SUM(COALESCE(i.monto,0)) as total")
            ->when(count($filterUserIds), fn($q) => $q->whereIn('u.id', $filterUserIds))
            ->groupBy('u.id','u.name')
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
