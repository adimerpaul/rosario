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
        $metodoPago = $request->query('metodo_pago');
        $username = $request->query('usuario');

        $auth = $request->user();
        $isAdmin = ($auth && ($auth->role ?? '') === 'Administrador');
        $user = null;
        $userForTotals = null;
        $isAdminFilteringByUser = false;
        $isOwnUserScope = false;

        if ($auth && ! $isAdmin) {
            $user = $auth;
            $userForTotals = $auth;
            $isOwnUserScope = true;
        } elseif ($username) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                $user = new User(['id' => 0]);
            }
            $userForTotals = $user;
            $isAdminFilteringByUser = true;
        }

        $suggested = $this->calculateSuggestedOpeningAmount($date, $userForTotals);
        $daily = $this->resolveDailyCash(
            $date,
            $suggested,
            optional($auth)->id,
            $isAdminFilteringByUser
        );

        $ordenes = \App\Models\Orden::with(['cliente', 'user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            // Las ordenes canceladas se mantienen: su reversion se registra como egreso
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $this->buildItemsOrdenAdelanto($ordenes);

        $pagosOrdenes = \App\Models\OrdenPago::with(['orden.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            // Los pagos anulados se mantienen: su reversion se registra como egreso
            ->whereIn('estado', ['Activo', 'Anulado'])
            ->orderBy('created_at')
            ->get();

        $itemsPagoOrden = $pagosOrdenes->map(function ($pago) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago orden '.$pago->orden->numero.' - '.($pago->orden->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($pago->metodo),
                'fuente' => 'PAGO ORDEN',
                'estado' => 'Activo',
                'key' => 'po-'.$pago->id,
            ];
        })->values();

        $pagosPrestamos = \App\Models\PrestamoPago::with(['prestamo.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->where(function ($q) {
                $q->whereNull('tipo_pago')
                    ->orWhere('tipo_pago', '!=', 'ADICIONAR CAPITAL');
            })
            ->orderBy('created_at')
            ->get();

        $itemsPagoPrest = $pagosPrestamos->map(function ($pago) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago prestamo '.$pago->prestamo->numero.' - '.($pago->prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($pago->metodo),
                'fuente' => 'PAGO PRESTAMO',
                'estado' => $pago->estado ?? 'Activo',
                'key' => 'pp-'.$pago->id,
            ];
        })->values();

        $ingresosOtros = \App\Models\Ingreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $itemsIngresoOtros = $ingresosOtros->map(function ($ingreso) {
            return [
                'id' => $ingreso->id,
                'hora' => optional($ingreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $ingreso->descripcion,
                'monto' => (float) $ingreso->monto,
                'usuario' => $ingreso->user->username ?? $ingreso->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($ingreso->metodo ?? 'EFECTIVO'),
                'fuente' => 'INGRESO',
                'estado' => $ingreso->estado ?? 'Activo',
                'key' => 'in-'.$ingreso->id,
            ];
        })->values();

        $itemsIngresos = collect()
            ->merge($itemsOrden)
            ->merge($itemsPagoOrden)
            ->merge($itemsPagoPrest)
            ->merge($itemsIngresoOtros)
            ->filter(fn ($item) => $this->passesMetodoFilter($item['metodo'] ?? null, $metodoPago))
            ->values();

        $prestamos = \App\Models\Prestamo::with(['cliente', 'user', 'pagos' => function ($q) {
            $q->where('estado', 'Activo')
                ->where('tipo_pago', 'ADICIONAR CAPITAL');
        }])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $this->buildItemsPrestamoOtorgado($prestamos);

        $pagosCapital = \App\Models\PrestamoPago::with(['prestamo.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->where('tipo_pago', 'ADICIONAR CAPITAL')
            ->orderBy('created_at')
            ->get();

        $itemsCapitalPrestamos = $pagosCapital->map(function ($pago) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Adicionar capital '.$pago->prestamo->numero.' - '.($pago->prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($pago->metodo ?? 'EFECTIVO'),
                'fuente' => 'ADICIONAR CAPITAL',
                'estado' => $pago->estado ?? 'Activo',
                'key' => 'pc-'.$pago->id,
            ];
        })->values();

        $egresosOtros = \App\Models\Egreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $itemsEgresoOtros = $egresosOtros->map(function ($egreso) {
            return [
                'id' => $egreso->id,
                'hora' => optional($egreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $egreso->descripcion,
                'monto' => (float) $egreso->monto,
                'usuario' => $egreso->user->username ?? $egreso->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($egreso->metodo ?? 'EFECTIVO'),
                'fuente' => 'EGRESO',
                'estado' => $egreso->estado ?? 'Activo',
                'key' => 'eg-'.$egreso->id,
            ];
        })->values();

        $itemsEgresos = collect()
            ->merge($itemsPrestamos)
            ->merge($itemsCapitalPrestamos)
            ->merge($itemsEgresoOtros)
            ->filter(fn ($item) => $this->passesMetodoFilter($item['metodo'] ?? null, $metodoPago))
            ->values();

        $sumActivos = fn ($items) => (float) collect($items)
            ->filter(fn ($item) => ($item['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        $itemsIngresosTotales = $this->filterItemsByMetodo(
            $this->buildItemsIngresos($date, $userForTotals),
            $metodoPago
        );
        $itemsEgresosTotales = $this->filterItemsByMetodo(
            $this->buildItemsEgresos($date, $userForTotals),
            $metodoPago
        );

        $ingresosSinCaja = $sumActivos($itemsIngresosTotales);
        $egresosTotal = $sumActivos($itemsEgresosTotales);
        $openingAmountFiltered = ($isAdminFilteringByUser || $isOwnUserScope)
            ? 0.0
            : $this->filterOpeningAmount((float) $daily->opening_amount, $metodoPago);

        $totalIngresos = $openingAmountFiltered + $ingresosSinCaja;
        $totalEgresos = $egresosTotal;
        $totalCaja = $openingAmountFiltered + $ingresosSinCaja - $egresosTotal;

        return response()->json([
            'date' => $date,
            'daily_cash' => $daily,
            'suggested_opening_amount' => round($suggested, 2),
            'filtered_opening_amount' => round($openingAmountFiltered, 2),
            'is_user_filtered' => ($isAdminFilteringByUser || $isOwnUserScope),
            'items_ingresos' => $itemsIngresos,
            'items_egresos' => $itemsEgresos,
            'total_ingresos' => round($totalIngresos, 2),
            'total_egresos' => round($totalEgresos, 2),
            'total_caja' => round($totalCaja, 2),
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
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

    private function resolveDailyCash(
        string $date,
        float $suggested,
        ?int $authUserId,
        bool $isAdminFilteringByUser
    ): DailyCash {
        $daily = DailyCash::query()->whereDate('date', $date)->first();
        $targetDate = Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();
        $canPersist = ! $isAdminFilteringByUser && $targetDate->lte($today);

        if (! $daily && ! $canPersist) {
            return new DailyCash([
                'date' => $date,
                'opening_amount' => $suggested,
                'user_id' => $authUserId,
            ]);
        }

        if (! $daily) {
            return DailyCash::create([
                'date' => $date,
                'opening_amount' => $suggested,
                'user_id' => $authUserId,
            ]);
        }

        if (
            $canPersist &&
            ! (bool) $daily->closed &&
            round((float) $daily->opening_amount, 2) !== round($suggested, 2)
        ) {
            $daily->opening_amount = $suggested;
            $daily->user_id = $daily->user_id ?: $authUserId;
            $daily->save();
            $daily->refresh();
        }

        return $daily;
    }

    private function calculateSuggestedOpeningAmount(string $date, ?User $user = null): float
    {
        $sumActivos = fn ($items) => (float) collect($items)
            ->filter(fn ($item) => ($item['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        $targetDate = Carbon::parse($date)->startOfDay();
        $lastDailyCash = DailyCash::query()
            ->where('date', '<', $targetDate->toDateString())
            ->orderByDesc('date')
            ->first();

        if (! $lastDailyCash) {
            $runningTotal = 0.0;
            $cursor = $targetDate->copy()->subDay();
        } else {
            $runningTotal = (float) $lastDailyCash->opening_amount;
            $cursor = Carbon::parse($lastDailyCash->date)->startOfDay();
        }

        $lastDayToInclude = $targetDate->copy()->subDay();

        while ($cursor->lte($lastDayToInclude)) {
            $currentDate = $cursor->toDateString();
            $runningTotal += $sumActivos($this->filterItemsByMetodo(
                $this->buildItemsIngresos($currentDate, $user),
                'EFECTIVO'
            ));
            $runningTotal -= $sumActivos($this->filterItemsByMetodo(
                $this->buildItemsEgresos($currentDate, $user),
                'EFECTIVO'
            ));
            $cursor->addDay();
        }

        return round($runningTotal, 2);
    }

    /**
     * Una linea por metodo (EFECTIVO / QR) del adelanto de cada orden.
     */
    private function buildItemsOrdenAdelanto($ordenes)
    {
        return $ordenes->flatMap(function ($orden) {
            $efectivo = (float) ($orden->adelanto_efectivo ?? 0);
            $qr = (float) ($orden->adelanto_qr ?? 0);

            // Registros sin desglose: todo al metodo de la orden
            if ($efectivo + $qr <= 0) {
                $metodo = $this->normalizeMetodo($orden->tipo_pago);
                $efectivo = $metodo === 'QR' ? 0 : (float) ($orden->adelanto ?? 0);
                $qr = $metodo === 'QR' ? (float) ($orden->adelanto ?? 0) : 0;
            }

            $mixto = $efectivo > 0 && $qr > 0;
            $base = [
                'id' => $orden->id,
                'hora' => Carbon::parse($orden->fecha_creacion)->format('H:i'),
                'descripcion' => 'Orden '.$orden->numero.' - '.($orden->cliente->name ?? 'N/A'),
                'usuario' => $orden->user->username ?? $orden->user->name ?? 'N/A',
                'fuente' => 'ORDEN',
                'estado' => 'Activo',
                'mixto' => $mixto,
            ];

            return collect([
                ['metodo' => 'EFECTIVO', 'monto' => $efectivo],
                ['metodo' => 'QR', 'monto' => $qr],
            ])->filter(fn ($parte) => $parte['monto'] > 0)
                ->map(fn ($parte) => array_merge($base, $parte, [
                    'key' => 'o-'.$orden->id.($mixto ? '-'.strtolower($parte['metodo']) : ''),
                ]))
                ->values();
        })->values();
    }

    private function buildItemsIngresos(string $date, ?User $user = null)
    {
        $ordenes = \App\Models\Orden::with(['cliente', 'user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            // Las ordenes canceladas se mantienen: su reversion se registra como egreso
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $this->buildItemsOrdenAdelanto($ordenes);

        $pagosOrdenes = \App\Models\OrdenPago::with(['orden.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            // Los pagos anulados se mantienen: su reversion se registra como egreso
            ->whereIn('estado', ['Activo', 'Anulado'])
            ->orderBy('created_at')
            ->get();

        $itemsPagoOrden = $pagosOrdenes->map(function ($pago) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago orden '.$pago->orden->numero.' - '.($pago->orden->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($pago->metodo),
                'fuente' => 'PAGO ORDEN',
                'estado' => 'Activo',
                'key' => 'po-'.$pago->id,
            ];
        })->values();

        $pagosPrestamos = \App\Models\PrestamoPago::with(['prestamo.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->where(function ($q) {
                $q->whereNull('tipo_pago')
                    ->orWhere('tipo_pago', '!=', 'ADICIONAR CAPITAL');
            })
            ->orderBy('created_at')
            ->get();

        $itemsPagoPrest = $pagosPrestamos->map(function ($pago) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago prestamo '.$pago->prestamo->numero.' - '.($pago->prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($pago->metodo),
                'fuente' => 'PAGO PRESTAMO',
                'estado' => $pago->estado ?? 'Activo',
                'key' => 'pp-'.$pago->id,
            ];
        })->values();

        $ingresosOtros = \App\Models\Ingreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $itemsIngresoOtros = $ingresosOtros->map(function ($ingreso) {
            return [
                'id' => $ingreso->id,
                'hora' => optional($ingreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $ingreso->descripcion,
                'monto' => (float) $ingreso->monto,
                'usuario' => $ingreso->user->username ?? $ingreso->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($ingreso->metodo ?? 'EFECTIVO'),
                'fuente' => 'INGRESO',
                'estado' => $ingreso->estado ?? 'Activo',
                'key' => 'in-'.$ingreso->id,
            ];
        })->values();

        return collect()
            ->merge($itemsOrden)
            ->merge($itemsPagoOrden)
            ->merge($itemsPagoPrest)
            ->merge($itemsIngresoOtros)
            ->values();
    }

    private function buildItemsEgresos(string $date, ?User $user = null)
    {
        $prestamos = \App\Models\Prestamo::with(['cliente', 'user', 'pagos' => function ($q) {
            $q->where('estado', 'Activo')
                ->where('tipo_pago', 'ADICIONAR CAPITAL');
        }])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $this->buildItemsPrestamoOtorgado($prestamos);

        $pagosCapital = \App\Models\PrestamoPago::with(['prestamo.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->where('tipo_pago', 'ADICIONAR CAPITAL')
            ->orderBy('created_at')
            ->get();

        $itemsCapitalPrestamos = $pagosCapital->map(function ($pago) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Adicionar capital '.$pago->prestamo->numero.' - '.($pago->prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($pago->metodo ?? 'EFECTIVO'),
                'fuente' => 'ADICIONAR CAPITAL',
                'estado' => $pago->estado ?? 'Activo',
                'key' => 'pc-'.$pago->id,
            ];
        })->values();

        $egresosOtros = \App\Models\Egreso::with(['user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get();

        $itemsEgresoOtros = $egresosOtros->map(function ($egreso) {
            return [
                'id' => $egreso->id,
                'hora' => optional($egreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $egreso->descripcion,
                'monto' => (float) $egreso->monto,
                'usuario' => $egreso->user->username ?? $egreso->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($egreso->metodo ?? 'EFECTIVO'),
                'fuente' => 'EGRESO',
                'estado' => $egreso->estado ?? 'Activo',
                'key' => 'eg-'.$egreso->id,
            ];
        })->values();

        return collect()
            ->merge($itemsPrestamos)
            ->merge($itemsCapitalPrestamos)
            ->merge($itemsEgresoOtros)
            ->values();
    }

    /**
     * Una linea por metodo (EFECTIVO / QR) del dinero entregado en cada prestamo.
     */
    private function buildItemsPrestamoOtorgado($prestamos)
    {
        return $prestamos->flatMap(function ($prestamo) {
            $montoInicial = $this->prestamoMontoInicial($prestamo);
            if ($montoInicial <= 0) {
                return [];
            }

            $partes = $prestamo->desgloseEntrega($montoInicial);
            $mixto = $partes['EFECTIVO'] > 0 && $partes['QR'] > 0;
            $base = [
                'id' => $prestamo->id,
                'hora' => Carbon::parse($prestamo->fecha_creacion)->format('H:i'),
                'descripcion' => 'Prestamo '.$prestamo->numero.' - '.($prestamo->cliente->name ?? 'N/A'),
                'usuario' => $prestamo->user->username ?? $prestamo->user->name ?? 'N/A',
                'fuente' => 'PRESTAMO OTORGADO',
                'estado' => 'Activo',
                'mixto' => $mixto,
                'monto_total' => $montoInicial,
                'entrega_efectivo' => $partes['EFECTIVO'],
                'entrega_qr' => $partes['QR'],
            ];

            return collect($partes)
                ->filter(fn ($monto) => $monto > 0)
                ->map(fn ($monto, $metodo) => array_merge($base, [
                    'metodo' => $metodo,
                    'monto' => $monto,
                    'key' => 'pr-'.$prestamo->id.($mixto ? '-'.strtolower($metodo) : ''),
                ]))
                ->values();
        })->values();
    }

    private function prestamoMontoInicial(\App\Models\Prestamo $prestamo): float
    {
        $capitalAdicionado = (float) collect($prestamo->pagos ?? [])
            ->filter(fn ($pago) => ($pago->estado ?? 'Activo') === 'Activo' && ($pago->tipo_pago ?? null) === 'ADICIONAR CAPITAL')
            ->sum('monto');

        return max(0, round((float) ($prestamo->valor_prestado ?? 0) - $capitalAdicionado, 2));
    }

    private function normalizeMetodo($metodo): ?string
    {
        $metodo = strtoupper(trim((string) $metodo));

        if ($metodo === 'CASH') {
            return 'EFECTIVO';
        }

        if ($metodo === '') {
            return null;
        }

        return $metodo;
    }

    private function passesMetodoFilter($metodo, ?string $metodoPago): bool
    {
        if (! $metodoPago) {
            return true;
        }

        return $this->normalizeMetodo($metodo) === $this->normalizeMetodo($metodoPago);
    }

    private function filterItemsByMetodo($items, ?string $metodoPago)
    {
        return collect($items)
            ->filter(fn ($item) => $this->passesMetodoFilter($item['metodo'] ?? null, $metodoPago))
            ->values();
    }

    private function filterOpeningAmount(float $openingAmount, ?string $metodoPago): float
    {
        if (! $metodoPago) {
            return $openingAmount;
        }

        return $this->normalizeMetodo($metodoPago) === 'EFECTIVO' ? $openingAmount : 0.0;
    }
}
