<?php

namespace App\Http\Controllers;

use App\Models\AlmacenMovimiento;
use App\Models\Egreso;
use App\Models\Joya;
use App\Models\Orden;
use App\Models\OrdenPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenPagoController extends Controller
{
    public function index(Orden $orden)
    {
        return $orden->pagos()->with('user')->orderByDesc('id')->get();
    }

    public function anular(Request $request, OrdenPago $pago)
    {
        return DB::transaction(function () use ($pago, $request) {
            if ($pago->estado === 'Anulado') {
                return response()->json(['message' => 'El pago ya esta anulado'], 409);
            }

            $pago->estado = 'Anulado';
            $pago->save();

            $orden = $pago->orden()->first();
            $pagado = $orden->pagos()->where('estado', 'Activo')->sum('monto');
            $orden->saldo = max(0, ($orden->costo_total ?? 0) - ($pagado + ($orden->adelanto ?? 0)));
            if ($orden->saldo > 0 && $orden->estado === 'Entregado') {
                $orden->estado = $orden->tipo === 'Venta directa' ? 'Reservado' : 'Pendiente';
            }
            $orden->save();
            $this->syncVentaDirectaJoyaVendida($orden->fresh());
            $this->syncVentaDirectaAlmacen($orden->fresh(), $request->user()?->id, 'ENTRADA AUTOMATICA POR ANULACION DE PAGO');

            Egreso::create([
                'fecha' => now()->toDateString(),
                'descripcion' => 'ANULACION PAGO ORDEN '.$orden->numero,
                'metodo' => $this->normalizarMetodoCaja($pago->metodo),
                'monto' => round((float) $pago->monto, 2),
                'estado' => 'Activo',
                'user_id' => $request->user()?->id,
                'nota' => 'Reversion automatica de pago anulado',
            ]);

            return response()->json(['message' => 'Pago anulado']);
        });
    }

    public function store(Request $request, Orden $orden)
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo' => 'nullable|string|max:50',
        ]);

        $pago = $orden->pagos()->create([
            'fecha' => now(),
            'monto' => $data['monto'],
            'metodo' => $data['metodo'] ?? 'EFECTIVO',
            'estado' => 'Activo',
            'user_id' => $request->user()->id ?? null,
        ]);

        $pagado = $orden->pagos()->where('estado', 'Activo')->sum('monto');
        $orden->saldo = max(0, $orden->costo_total - $pagado - $orden->adelanto);
        if ($orden->estado !== 'Cancelada') {
            $orden->estado = $orden->saldo <= 0
                ? 'Entregado'
                : ($orden->tipo === 'Venta directa' ? 'Reservado' : 'Pendiente');
        }
        $orden->save();
        $this->syncVentaDirectaJoyaVendida($orden->fresh());
        $this->syncVentaDirectaAlmacen($orden->fresh(), $request->user()?->id, 'SALIDA AUTOMATICA POR PAGO DE VENTA');

        return $pago->load('user');
    }

    public function update(OrdenPago $pago)
    {
        if ($pago->estado === 'Anulado') {
            return response()->json(['message' => 'El pago ya esta anulado'], 400);
        }

        $orden = Orden::findOrFail($pago->orden_id);

        $orden->saldo += $pago->monto;
        if ($orden->saldo > 0) {
            $orden->estado = $orden->tipo === 'Venta directa' ? 'Reservado' : 'Pendiente';
        } else {
            $orden->estado = 'Entregado';
        }
        $orden->save();
        $this->syncVentaDirectaJoyaVendida($orden->fresh());
        $this->syncVentaDirectaAlmacen($orden->fresh(), null, 'ENTRADA AUTOMATICA POR REVERSION DE PAGO');

        $pago->update([
            'estado' => 'Anulado',
        ]);

        return response()->json(['message' => 'Pago anulado correctamente'], 200);
    }

    public function toggleMetodo(Request $request, OrdenPago $pago)
    {
        $user = $request->user();
        if (! $user || ($user->role ?? null) !== 'Administrador') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (($pago->estado ?? 'Activo') !== 'Activo') {
            return response()->json(['message' => 'Solo se puede cambiar el metodo en pagos activos'], 422);
        }

        $actual = strtoupper((string) $pago->metodo);
        $nuevoMetodo = $actual === 'QR' ? 'EFECTIVO' : 'QR';

        $pago->update(['metodo' => $nuevoMetodo]);

        return response()->json($pago->fresh()->load('user'));
    }

    private function normalizarMetodoCaja(?string $metodo): string
    {
        $valor = strtoupper(trim((string) $metodo));

        return match ($valor) {
            'QR' => 'QR',
            'EFECTIVO' => 'EFECTIVO',
            default => 'EFECTIVO',
        };
    }

    private function syncVentaDirectaAlmacen(Orden $orden, ?int $userId, ?string $observacion = null): void
    {
        if ($orden->tipo !== 'Venta directa') {
            return;
        }

        $latestMovement = AlmacenMovimiento::where('orden_id', $orden->id)
            ->orderByDesc('id')
            ->first();

        $isInWarehouse = $latestMovement?->tipo_movimiento === 'ENTRADA';
        $shouldBeInWarehouse = ($orden->estado !== 'Cancelada') && (float) ($orden->saldo ?? 0) > 0;

        if ($shouldBeInWarehouse && ! $isInWarehouse) {
            AlmacenMovimiento::create([
                'orden_id' => $orden->id,
                'prestamo_id' => null,
                'user_id' => $userId ?: $orden->user_id,
                'tipo_movimiento' => 'ENTRADA',
                'fecha_movimiento' => now(),
                'observacion' => $observacion ?: 'ENTRADA AUTOMATICA POR VENTA RESERVADA',
            ]);

            return;
        }

        if (! $shouldBeInWarehouse && $isInWarehouse) {
            AlmacenMovimiento::create([
                'orden_id' => $orden->id,
                'prestamo_id' => $latestMovement?->prestamo_id,
                'user_id' => $userId ?: $orden->user_id,
                'tipo_movimiento' => 'SALIDA',
                'fecha_movimiento' => now(),
                'observacion' => $observacion ?: 'SALIDA AUTOMATICA POR ENTREGA DE VENTA',
            ]);
        }
    }

    private function syncVentaDirectaJoyaVendida(Orden $orden): void
    {
        if ($orden->tipo !== 'Venta directa') {
            return;
        }

        $vendida = $orden->estado !== 'Cancelada';

        $orden->joyas()->update(['vendido' => $vendida]);

        if ($orden->joya_id) {
            Joya::where('id', $orden->joya_id)->update(['vendido' => $vendida]);
        }
    }
}
