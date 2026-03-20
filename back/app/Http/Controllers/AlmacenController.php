<?php

namespace App\Http\Controllers;

use App\Models\AlmacenMovimiento;
use App\Models\Orden;
use App\Models\Prestamo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AlmacenController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeWarehouseAccess($request);

        $search = trim((string) $request->query('search', ''));
        $currentIds = $this->currentStockOrderIds();

        $actuales = $this->currentStockQuery()
            ->when($search !== '', function (Builder $query) use ($search) {
                $this->applySearch($query, $search);
            })
            ->orderByDesc('fecha_movimiento')
            ->get()
            ->map(fn (AlmacenMovimiento $movimiento) => $this->mapCurrentMovement($movimiento))
            ->values();

        $historial = AlmacenMovimiento::with([
            'orden.cliente:id,name,ci',
            'prestamo:id,numero',
            'user:id,name,username',
        ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $this->applySearch($query, $search);
            })
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (AlmacenMovimiento $movimiento) => $this->mapHistoryMovement($movimiento))
            ->values();

        $disponibles = Orden::with(['cliente:id,name,ci'])
            ->where('tipo', 'Orden')
            ->where('estado', '!=', 'Cancelada')
            ->whereNotIn('id', $currentIds)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('numero', 'like', "%{$search}%")
                        ->orWhere('detalle', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function (Builder $cliente) use ($search) {
                            $cliente->where('name', 'like', "%{$search}%")
                                ->orWhere('ci', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('fecha_creacion')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn (Orden $orden) => $this->mapAvailableOrder($orden))
            ->values();

        return response()->json([
            'summary' => [
                'cantidad_actual' => $actuales->count(),
                'entradas_hoy' => AlmacenMovimiento::where('tipo_movimiento', 'ENTRADA')
                    ->whereDate('fecha_movimiento', today())
                    ->count(),
                'salidas_hoy' => AlmacenMovimiento::where('tipo_movimiento', 'SALIDA')
                    ->whereDate('fecha_movimiento', today())
                    ->count(),
            ],
            'actuales' => $actuales,
            'historial' => $historial,
            'disponibles' => $disponibles,
        ]);
    }

    public function entrar(Request $request)
    {
        $this->authorizeWarehouseAccess($request);

        $data = $request->validate([
            'orden_id' => 'required|exists:ordenes,id',
            'prestamo_id' => 'nullable|exists:prestamos,id',
            'observacion' => 'nullable|string|max:1000',
        ]);

        $orden = Orden::with('cliente:id,name,ci')->findOrFail($data['orden_id']);

        if ($orden->tipo !== 'Orden') {
            return response()->json(['message' => 'Solo las ordenes de trabajo pueden ingresar al almacen'], 422);
        }

        if (($orden->estado ?? '') === 'Cancelada') {
            return response()->json(['message' => 'No se puede ingresar una orden cancelada al almacen'], 422);
        }

        if ($this->latestMovementTypeForOrder($orden->id) === 'ENTRADA') {
            return response()->json(['message' => 'La orden ya se encuentra en el almacen'], 422);
        }

        $prestamoId = $data['prestamo_id'] ?? $this->suggestPrestamoIdForClient($orden->cliente_id);

        $movimiento = DB::transaction(function () use ($request, $orden, $prestamoId, $data) {
            return AlmacenMovimiento::create([
                'orden_id' => $orden->id,
                'prestamo_id' => $prestamoId,
                'user_id' => $request->user()->id,
                'tipo_movimiento' => 'ENTRADA',
                'fecha_movimiento' => now(),
                'observacion' => $data['observacion'] ?? null,
            ]);
        });

        return response()->json(
            $this->mapHistoryMovement(
                $movimiento->load(['orden.cliente:id,name,ci', 'prestamo:id,numero', 'user:id,name,username'])
            ),
            201
        );
    }

    public function salir(Request $request)
    {
        $this->authorizeWarehouseAccess($request);

        $data = $request->validate([
            'orden_id' => 'required|exists:ordenes,id',
            'observacion' => 'nullable|string|max:1000',
        ]);

        $ultimaEntrada = $this->currentStockQuery()
            ->where('orden_id', $data['orden_id'])
            ->first();

        if (! $ultimaEntrada) {
            return response()->json(['message' => 'La orden no se encuentra en el almacen'], 422);
        }

        $movimiento = DB::transaction(function () use ($request, $data, $ultimaEntrada) {
            return AlmacenMovimiento::create([
                'orden_id' => $data['orden_id'],
                'prestamo_id' => $ultimaEntrada->prestamo_id,
                'user_id' => $request->user()->id,
                'tipo_movimiento' => 'SALIDA',
                'fecha_movimiento' => now(),
                'observacion' => $data['observacion'] ?? null,
            ]);
        });

        return response()->json(
            $this->mapHistoryMovement(
                $movimiento->load(['orden.cliente:id,name,ci', 'prestamo:id,numero', 'user:id,name,username'])
            ),
            201
        );
    }

    private function authorizeWarehouseAccess(Request $request): void
    {
        $role = $request->user()?->role;

        abort_unless(in_array($role, ['Administrador', 'Vendedor'], true), 403, 'No autorizado');
    }

    private function currentStockQuery(): Builder
    {
        $latestPerOrder = AlmacenMovimiento::selectRaw('MAX(id) as id')
            ->groupBy('orden_id');

        return AlmacenMovimiento::with([
            'orden.cliente:id,name,ci',
            'prestamo:id,numero',
            'user:id,name,username',
        ])
            ->whereIn('id', $latestPerOrder)
            ->where('tipo_movimiento', 'ENTRADA');
    }

    private function currentStockOrderIds(): array
    {
        return $this->currentStockQuery()->pluck('orden_id')->all();
    }

    private function latestMovementTypeForOrder(int $ordenId): ?string
    {
        return AlmacenMovimiento::where('orden_id', $ordenId)
            ->orderByDesc('id')
            ->value('tipo_movimiento');
    }

    private function suggestPrestamoIdForClient(?int $clienteId): ?int
    {
        if (! $clienteId) {
            return null;
        }

        return Prestamo::where('cliente_id', $clienteId)
            ->whereIn('estado', ['Activo', 'Pendiente', 'Entregado'])
            ->orderByDesc('fecha_creacion')
            ->value('id');
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->whereHas('orden', function (Builder $orden) use ($search) {
            $orden->where('numero', 'like', "%{$search}%")
                ->orWhere('detalle', 'like', "%{$search}%")
                ->orWhereHas('cliente', function (Builder $cliente) use ($search) {
                    $cliente->where('name', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%");
                });
        });
    }

    private function mapCurrentMovement(AlmacenMovimiento $movimiento): array
    {
        $fecha = Carbon::parse($movimiento->fecha_movimiento);

        return [
            'id' => $movimiento->id,
            'orden_id' => $movimiento->orden_id,
            'orden_numero' => $movimiento->orden?->numero,
            'cliente' => $movimiento->orden?->cliente?->name,
            'cliente_ci' => $movimiento->orden?->cliente?->ci,
            'detalle' => $movimiento->orden?->detalle,
            'imagen' => $movimiento->orden?->foto_modelo,
            'estado_orden' => $movimiento->orden?->estado,
            'fecha_entrada' => $fecha->toDateTimeString(),
            'usuario_entrada' => $movimiento->user?->name ?? $movimiento->user?->username,
            'dias_en_almacen' => $fecha->diffInDays(now()),
            'tiempo_en_almacen' => $fecha->diffForHumans(now(), [
                'parts' => 2,
                'short' => true,
                'syntax' => Carbon::DIFF_ABSOLUTE,
            ]),
            'observacion' => $movimiento->observacion,
        ];
    }

    private function mapHistoryMovement(AlmacenMovimiento $movimiento): array
    {
        return [
            'id' => $movimiento->id,
            'tipo_movimiento' => $movimiento->tipo_movimiento,
            'orden_id' => $movimiento->orden_id,
            'orden_numero' => $movimiento->orden?->numero,
            'cliente' => $movimiento->orden?->cliente?->name,
            'detalle' => $movimiento->orden?->detalle,
            'imagen' => $movimiento->orden?->foto_modelo,
            'usuario' => $movimiento->user?->name ?? $movimiento->user?->username,
            'fecha_movimiento' => optional($movimiento->fecha_movimiento)?->toDateTimeString(),
            'observacion' => $movimiento->observacion,
        ];
    }

    private function mapAvailableOrder(Orden $orden): array
    {
        return [
            'id' => $orden->id,
            'numero' => $orden->numero,
            'cliente' => $orden->cliente?->name,
            'cliente_ci' => $orden->cliente?->ci,
            'detalle' => $orden->detalle,
            'imagen' => $orden->foto_modelo,
            'estado' => $orden->estado,
            'fecha_creacion' => $orden->fecha_creacion,
            'fecha_entrega' => $orden->fecha_entrega,
        ];
    }
}
