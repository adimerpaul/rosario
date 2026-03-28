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

        if ($auth && ($auth->role ?? '') !== 'Administrador') {
            $user = $auth;
            $userForTotals = $auth;
        } elseif ($username) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                $user = new User(['id' => 0]);
            }
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

        $normMetodo = function ($metodo) {
            $metodo = strtoupper(trim((string) $metodo));
            if ($metodo === 'CASH') return 'EFECTIVO';
            if ($metodo === '') return null;
            return $metodo;
        };

        $passMetodo = function ($metodo) use ($metodoPago, $normMetodo) {
            if (! $metodoPago) return true;
            return $normMetodo($metodo) === $normMetodo($metodoPago);
        };

        $ordenes = \App\Models\Orden::with(['cliente', 'user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $ordenes->map(function ($orden) use ($normMetodo) {
            return [
                'id' => $orden->id,
                'hora' => Carbon::parse($orden->fecha_creacion)->format('H:i'),
                'descripcion' => 'Orden '.$orden->numero.' - '.($orden->cliente->name ?? 'N/A'),
                'monto' => (float) ($orden->adelanto ?? 0),
                'usuario' => $orden->user->username ?? $orden->user->name ?? 'N/A',
                'metodo' => $normMetodo($orden->tipo_pago),
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

        $itemsPagoOrden = $pagosOrdenes->map(function ($pago) use ($normMetodo) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago orden '.$pago->orden->numero.' - '.($pago->orden->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $normMetodo($pago->metodo),
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

        $itemsPagoPrest = $pagosPrestamos->map(function ($pago) use ($normMetodo) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago prestamo '.$pago->prestamo->numero.' - '.($pago->prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $normMetodo($pago->metodo),
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

        $itemsIngresoOtros = $ingresosOtros->map(function ($ingreso) use ($normMetodo) {
            return [
                'id' => $ingreso->id,
                'hora' => optional($ingreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $ingreso->descripcion,
                'monto' => (float) $ingreso->monto,
                'usuario' => $ingreso->user->username ?? $ingreso->user->name ?? 'N/A',
                'metodo' => $normMetodo($ingreso->metodo ?? 'EFECTIVO'),
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
            ->filter(fn ($item) => $passMetodo($item['metodo'] ?? null))
            ->values();

        $prestamos = \App\Models\Prestamo::with(['cliente', 'user'])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $prestamos->map(function ($prestamo) use ($normMetodo) {
            $metodoColumn = $this->prestamoMetodoColumn();

            return [
                'id' => $prestamo->id,
                'hora' => Carbon::parse($prestamo->fecha_creacion)->format('H:i'),
                'descripcion' => 'Prestamo '.$prestamo->numero.' - '.($prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) ($prestamo->valor_prestado ?? 0),
                'usuario' => $prestamo->user->username ?? $prestamo->user->name ?? 'N/A',
                'metodo' => $normMetodo($metodoColumn ? $prestamo->{$metodoColumn} : 'EFECTIVO'),
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

        $itemsEgresoOtros = $egresosOtros->map(function ($egreso) use ($normMetodo) {
            return [
                'id' => $egreso->id,
                'hora' => optional($egreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $egreso->descripcion,
                'monto' => (float) $egreso->monto,
                'usuario' => $egreso->user->username ?? $egreso->user->name ?? 'N/A',
                'metodo' => $normMetodo($egreso->metodo ?? 'EFECTIVO'),
                'fuente' => 'EGRESO',
                'estado' => $egreso->estado ?? 'Activo',
                'key' => 'eg-'.$egreso->id,
            ];
        })->values();

        $itemsEgresos = collect()
            ->merge($itemsPrestamos)
            ->merge($itemsEgresoOtros)
            ->filter(fn ($item) => $passMetodo($item['metodo'] ?? null))
            ->values();

        $sumActivos = fn ($items) => (float) collect($items)
            ->filter(fn ($item) => ($item['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        $itemsIngresosTotales = $this->buildItemsIngresos($date, $userForTotals);
        $itemsEgresosTotales = $this->buildItemsEgresos($date, $userForTotals);

        $ingresosSinCaja = $sumActivos($itemsIngresosTotales);
        $egresosTotal = $sumActivos($itemsEgresosTotales);

        $totalIngresos = (float) $daily->opening_amount + $ingresosSinCaja;
        $totalEgresos = $egresosTotal;
        $totalCaja = (float) $daily->opening_amount + $ingresosSinCaja - $egresosTotal;

        return response()->json([
            'date' => $date,
            'daily_cash' => $daily,
            'suggested_opening_amount' => round($suggested, 2),
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
        $yesterday = Carbon::parse($date)->subDay()->toDateString();
        $opening = (float) (DailyCash::where('date', $yesterday)->value('opening_amount') ?? 0);

        $sumActivos = fn ($items) => (float) collect($items)
            ->filter(fn ($item) => ($item['estado'] ?? 'Activo') === 'Activo')
            ->sum('monto');

        $ingresos = $sumActivos($this->buildItemsIngresos($yesterday, $user));
        $egresos = $sumActivos($this->buildItemsEgresos($yesterday, $user));

        return round($opening + $ingresos - $egresos, 2);
    }

    private function buildItemsIngresos(string $date, ?User $user = null)
    {
        $normMetodo = function ($metodo) {
            $metodo = strtoupper(trim((string) $metodo));
            if ($metodo === 'CASH') return 'EFECTIVO';
            if ($metodo === '') return null;
            return $metodo;
        };

        $ordenes = \App\Models\Orden::with(['cliente', 'user'])
            ->whereDate('fecha_creacion', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('estado', '!=', 'Cancelada')
            ->orderBy('fecha_creacion')
            ->get();

        $itemsOrden = $ordenes->map(function ($orden) use ($normMetodo) {
            return [
                'id' => $orden->id,
                'hora' => Carbon::parse($orden->fecha_creacion)->format('H:i'),
                'descripcion' => 'Orden '.$orden->numero.' - '.($orden->cliente->name ?? 'N/A'),
                'monto' => (float) ($orden->adelanto ?? 0),
                'usuario' => $orden->user->username ?? $orden->user->name ?? 'N/A',
                'metodo' => $normMetodo($orden->tipo_pago),
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

        $itemsPagoOrden = $pagosOrdenes->map(function ($pago) use ($normMetodo) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago orden '.$pago->orden->numero.' - '.($pago->orden->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $normMetodo($pago->metodo),
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

        $itemsPagoPrest = $pagosPrestamos->map(function ($pago) use ($normMetodo) {
            return [
                'id' => $pago->id,
                'hora' => optional($pago->created_at)->format('H:i') ?: '-',
                'descripcion' => 'Pago prestamo '.$pago->prestamo->numero.' - '.($pago->prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) $pago->monto,
                'usuario' => $pago->user->username ?? $pago->user->name ?? 'N/A',
                'metodo' => $normMetodo($pago->metodo),
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

        $itemsIngresoOtros = $ingresosOtros->map(function ($ingreso) use ($normMetodo) {
            return [
                'id' => $ingreso->id,
                'hora' => optional($ingreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $ingreso->descripcion,
                'monto' => (float) $ingreso->monto,
                'usuario' => $ingreso->user->username ?? $ingreso->user->name ?? 'N/A',
                'metodo' => $normMetodo($ingreso->metodo ?? 'EFECTIVO'),
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
        $normMetodo = function ($metodo) {
            $metodo = strtoupper(trim((string) $metodo));
            if ($metodo === 'CASH') return 'EFECTIVO';
            if ($metodo === '') return null;
            return $metodo;
        };

        $prestamos = \App\Models\Prestamo::with(['cliente', 'user'])
            ->whereDate('prestamos.created_at', $date)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('prestamos.created_at')
            ->get();

        $itemsPrestamos = $prestamos->map(function ($prestamo) use ($normMetodo) {
            $metodoColumn = $this->prestamoMetodoColumn();

            return [
                'id' => $prestamo->id,
                'hora' => Carbon::parse($prestamo->fecha_creacion)->format('H:i'),
                'descripcion' => 'Prestamo '.$prestamo->numero.' - '.($prestamo->cliente->name ?? 'N/A'),
                'monto' => (float) ($prestamo->valor_prestado ?? 0),
                'usuario' => $prestamo->user->username ?? $prestamo->user->name ?? 'N/A',
                'metodo' => $normMetodo($metodoColumn ? $prestamo->{$metodoColumn} : 'EFECTIVO'),
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

        $itemsEgresoOtros = $egresosOtros->map(function ($egreso) use ($normMetodo) {
            return [
                'id' => $egreso->id,
                'hora' => optional($egreso->created_at)->format('H:i') ?: '-',
                'descripcion' => $egreso->descripcion,
                'monto' => (float) $egreso->monto,
                'usuario' => $egreso->user->username ?? $egreso->user->name ?? 'N/A',
                'metodo' => $normMetodo($egreso->metodo ?? 'EFECTIVO'),
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
