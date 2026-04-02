<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DailyCashController extends Controller
{
    private ?string $prestamoMetodoColumn = null;

    public function show(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $metodoPago = $request->query('metodo_pago');
        $username = $request->query('usuario');

        $auth = $request->user();
        $user = null;
        $userForTotals = null;
        $isAdminFilteringByUser = false;

        if ($auth && ($auth->role ?? '') !== 'Administrador') {
            $user = $auth;
            $userForTotals = $auth;
        } elseif ($username) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                $user = new User(['id' => 0]);
            }
            $userForTotals = $user;
            $isAdminFilteringByUser = true;
        }

        $suggested = $this->calculateSuggestedOpeningAmount($date, $userForTotals);

        $daily = DailyCash::firstOrCreate(
            ['date' => $date],
            ['opening_amount' => $suggested, 'user_id' => optional($auth)->id]
        );

        if ((float) $daily->opening_amount <= 0 && $suggested > 0) {
            $daily->opening_amount = $suggested;
            $daily->user_id = $daily->user_id ?: optional($auth)->id;
            $daily->save();
            $daily->refresh();
        }

        if (
            ! $isAdminFilteringByUser &&
            ! $this->dateHasMovements($date, $userForTotals) &&
            round((float) $daily->opening_amount, 2) !== round($suggested, 2)
        ) {
            $daily->opening_amount = $suggested;
            $daily->user_id = $daily->user_id ?: optional($auth)->id;
            $daily->save();
            $daily->refresh();
        }

        $ordenes = \App\Models\Orden::with(['cliente', 'user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $ordenes->map(function ($orden) {
            return [
                'id' => $orden->id,
                'hora' => Carbon::parse($orden->fecha_creacion)->format('H:i'),
                'descripcion' => 'Orden '.$orden->numero.' - '.($orden->cliente->name ?? 'N/A'),
                'monto' => (float) ($orden->adelanto ?? 0),
                'usuario' => $orden->user->username ?? $orden->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($orden->tipo_pago),
                'fuente' => 'ORDEN',
                'estado' => 'Activo',
                'key' => 'o-'.$orden->id,
            ];
        })->filter(fn ($item) => $item['monto'] > 0)->values();

        $pagosOrdenes = \App\Models\OrdenPago::with(['orden.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->whereHas('orden', fn ($q) => $q->where('estado', '!=', 'Cancelada'))
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
                'estado' => $pago->estado ?? 'Activo',
                'key' => 'po-'.$pago->id,
            ];
        })->values();

        $pagosPrestamos = \App\Models\PrestamoPago::with(['prestamo.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
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

        $prestamos = \App\Models\Prestamo::with(['cliente', 'user'])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $prestamos->map(function ($prestamo) {
            $metodoColumn = $this->prestamoMetodoColumn();

            return [
                'id' => $prestamo->id,
                'hora' => Carbon::parse($prestamo->fecha_creacion)->format('H:i'),
                'descripcion' => 'Prestamo '.$prestamo->numero.' - '.($prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) ($prestamo->valor_prestado ?? 0),
                'usuario' => $prestamo->user->username ?? $prestamo->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($metodoColumn ? $prestamo->{$metodoColumn} : 'EFECTIVO'),
                'fuente' => 'PRESTAMO OTORGADO',
                'estado' => 'Activo',
                'key' => 'pr-'.$prestamo->id,
            ];
        })->filter(fn ($item) => $item['monto'] > 0)->values();

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
        $openingAmountFiltered = $isAdminFilteringByUser
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
            'is_user_filtered' => $isAdminFilteringByUser,
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
            $runningTotal += $sumActivos($this->buildItemsIngresos($currentDate, $user));
            $runningTotal -= $sumActivos($this->buildItemsEgresos($currentDate, $user));
            $cursor->addDay();
        }

        return round($runningTotal, 2);
    }

    private function buildItemsIngresos(string $date, ?User $user = null)
    {
        $ordenes = \App\Models\Orden::with(['cliente', 'user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $ordenes->map(function ($orden) {
            return [
                'id' => $orden->id,
                'hora' => Carbon::parse($orden->fecha_creacion)->format('H:i'),
                'descripcion' => 'Orden '.$orden->numero.' - '.($orden->cliente->name ?? 'N/A'),
                'monto' => (float) ($orden->adelanto ?? 0),
                'usuario' => $orden->user->username ?? $orden->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($orden->tipo_pago),
                'fuente' => 'ORDEN',
                'estado' => 'Activo',
                'key' => 'o-'.$orden->id,
            ];
        })->filter(fn ($item) => $item['monto'] > 0)->values();

        $pagosOrdenes = \App\Models\OrdenPago::with(['orden.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
            ->whereHas('orden', fn ($q) => $q->where('estado', '!=', 'Cancelada'))
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
                'estado' => $pago->estado ?? 'Activo',
                'key' => 'po-'.$pago->id,
            ];
        })->values();

        $pagosPrestamos = \App\Models\PrestamoPago::with(['prestamo.cliente', 'user'])
            ->whereDate('fecha', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', 'Activo')
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

    private function dateHasMovements(string $date, ?User $user = null): bool
    {
        return $this->buildItemsIngresos($date, $user)->isNotEmpty()
            || $this->buildItemsEgresos($date, $user)->isNotEmpty();
    }

    private function buildItemsEgresos(string $date, ?User $user = null)
    {
        $prestamos = \App\Models\Prestamo::with(['cliente', 'user'])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $prestamos->map(function ($prestamo) {
            $metodoColumn = $this->prestamoMetodoColumn();

            return [
                'id' => $prestamo->id,
                'hora' => Carbon::parse($prestamo->fecha_creacion)->format('H:i'),
                'descripcion' => 'Prestamo '.$prestamo->numero.' - '.($prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) ($prestamo->valor_prestado ?? 0),
                'usuario' => $prestamo->user->username ?? $prestamo->user->name ?? 'N/A',
                'metodo' => $this->normalizeMetodo($metodoColumn ? $prestamo->{$metodoColumn} : 'EFECTIVO'),
                'fuente' => 'PRESTAMO OTORGADO',
                'estado' => 'Activo',
                'key' => 'pr-'.$prestamo->id,
            ];
        })->filter(fn ($item) => $item['monto'] > 0)->values();

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
            ->merge($itemsEgresoOtros)
            ->values();
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

    private function prestamoMetodoColumn(): ?string
    {
        if ($this->prestamoMetodoColumn !== null) {
            return $this->prestamoMetodoColumn;
        }

        if (Schema::hasColumn('prestamos', 'metodo')) {
            return $this->prestamoMetodoColumn = 'metodo';
        }

        if (Schema::hasColumn('prestamos', 'metodo_entrega')) {
            return $this->prestamoMetodoColumn = 'metodo_entrega';
        }

        return $this->prestamoMetodoColumn = '';
    }
}
