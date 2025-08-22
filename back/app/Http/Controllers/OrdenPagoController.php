<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\OrdenPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Translation\t;

class OrdenPagoController extends Controller{
    public function index(Orden $orden)
    {
        return $orden->pagos()->with('user')->orderByDesc('id')->get();
    }
    public function anular(OrdenPago $pago)
    {
        return DB::transaction(function () use ($pago) {
            if ($pago->estado === 'Anulado') {
                return response()->json(['message' => 'El pago ya está anulado'], 409);
            }

            $pago->estado = 'Anulado';
            $pago->save();

            $orden = $pago->orden()->first();
            $pagado = $orden->pagos()->where('estado','Activo')->sum('monto');
            $orden->adelanto = $pagado;
            $orden->saldo = max(0, ($orden->costo_total ?? 0) - $pagado);
            if ($orden->saldo > 0 && $orden->estado === 'Entregado') {
                $orden->estado = 'Pendiente'; // si corresponde
            }
            $orden->save();

            return response()->json(['message' => 'Pago anulado']);
        });
    }

    public function store(Request $request, Orden $orden)
    {
        $data = $request->validate([
            'monto'  => 'required|numeric|min:0.01',
            'metodo' => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($orden, $data, $request) {
            $pago = $orden->pagos()->create([
                'fecha'  => now(),
                'monto'  => $data['monto'],
                'metodo' => $data['metodo'] ?? 'EFECTIVO',
                'estado' => 'Activo',
                'user_id'=> $request->user()->id ?? null
            ]);

            $pagado = $orden->pagos()->where('estado','Activo')->sum('monto');
            $orden->adelanto = $pagado;
            $orden->saldo = max(0, ($orden->costo_total ?? 0) - $pagado);
            if ($orden->saldo <= 0 && $orden->estado !== 'Cancelada') {
                $orden->estado = 'Entregado'; // opcional, según tu flujo
            }
            $orden->save();

            return $pago->load('user');
        });
    }

    public function update(OrdenPago $pago){
        if ($pago->estado === 'Anulado') {
            return response()->json(['message' => 'El pago ya está anulado'], 400);
        }
        $orden = Orden::findOrFail($pago->orden_id);
//        if ($orden->estado !== 'Pendiente') {
//            return response()->json(['message' => 'La orden no está en estado pendiente'], 400);
//        }
        // Actualizar el saldo de la orden
        $orden->saldo += $pago->monto;

//        error_log("Saldo de la orden actualizado: " . $orden->saldo);
        if ($orden->saldo > 0) {
            $orden->estado = 'Pendiente'; // Cambiar estado a Pendiente si el saldo es mayor a 0
        } else {
            $orden->estado = 'Entregado'; // Cambiar estado a Entregado si el saldo es 0 o menor
        }
        $orden->save();
        $pago->update([
            'estado' => 'Anulado'
        ]);
        return response()->json(['message' => 'Pago anulado correctamente'], 200);
    }
}
