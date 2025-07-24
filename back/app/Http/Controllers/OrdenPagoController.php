<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\OrdenPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Translation\t;

class OrdenPagoController extends Controller{
    public function index($ordenId)
    {
        return OrdenPago::with('user:id,name')
            ->where('orden_id', $ordenId)
            ->orderBy('fecha', 'desc')
            ->get();
    }

    public function store(Request $request){
        $request->validate([
            'orden_id' => 'required|exists:ordenes,id',
            'monto' => 'required|numeric|min:1',
        ]);
        DB::beginTransaction();
        try {
            $user = $request->user();
            $orden = Orden::findOrFail($request->orden_id);
            if ($orden->estado !== 'Pendiente') {
                return response()->json(['message' => 'La orden no está en estado pendiente'], 400);
            }
            if ($orden->saldo < $request->monto) {
                return response()->json(['message' => 'El monto del pago no puede ser mayor al saldo de la orden'], 400);
            }
            // Actualizar el saldo de la orden
            $orden->saldo -= $request->monto;
            $orden->save();

            if ($orden->saldo <= 0) {
                $orden->estado = 'Entregado'; // Cambiar estado a Entregado si el saldo es 0 o menor
                $orden->save();
            }

            $pago = OrdenPago::create([
                'orden_id' => $request->orden_id,
                'monto' => $request->monto,
                'fecha' => now(),
                'user_id' => $user->id,
                'estado' => 'Activo'
            ]);
            DB::commit();
            return response()->json($pago, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al crear el pago: ' . $e->getMessage()], 500);
        }

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
