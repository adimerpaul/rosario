<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\PrestamoPago;
use App\Models\Cog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $fi = $request->query('fecha_inicio');
        $ff = $request->query('fecha_fin');
        $userId = $request->query('user_id');
        $estado = $request->query('estado', 'Todos');

        $q = Prestamo::with(['cliente','user'])->orderBy('id', 'desc');

        if ($fi) $q->whereDate('fecha_creacion', '>=', $fi);
        if ($ff) $q->whereDate('fecha_creacion', '<=', $ff);
        if ($userId) $q->where('user_id', $userId);
        if ($estado && $estado !== 'Todos') $q->where('estado', $estado);

        // si quieres paginar:
        // return $q->paginate(20);
        return $q->get();
    }

    public function show(Prestamo $prestamo)
    {
        return $prestamo->load(['cliente','user']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'     => 'required|integer',
            'user_id'        => 'required|integer',
            'fecha_creacion' => 'nullable|date',
            'fecha_limite'   => 'nullable|date',
            'peso'           => 'required|numeric|min:0',
            'valor_prestado' => 'required|numeric|min:0',
            'interes'        => 'required|numeric|min:0',
            'celular'        => 'nullable|string',
            'detalle'        => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $precioOro = optional(Cog::find(1))->value ?? 0; // id=1 = precio compra
            $valorTotal = ($data['peso'] ?? 0) * $precioOro;
            $saldo = ($data['valor_prestado'] ?? 0) + ($data['interes'] ?? 0);

            $prestamo = Prestamo::create([
                'numero'         => null,
                'fecha_creacion' => $data['fecha_creacion'] ?? now()->toDateString(),
                'fecha_limite'   => $data['fecha_limite'] ?? null,
                'cliente_id'     => $data['cliente_id'],
                'user_id'        => $data['user_id'],
                'peso'           => $data['peso'],
                'precio_oro'     => $precioOro,
                'valor_total'    => $valorTotal,
                'valor_prestado' => $data['valor_prestado'],
                'interes'        => $data['interes'],
                'saldo'          => $saldo,
                'celular'        => $data['celular'] ?? null,
                'detalle'        => $data['detalle'] ?? null,
                'estado'         => 'Pendiente',
            ]);

            // asignar número (ej: PR-000123-2025)
            $prestamo->numero = 'PR-'.str_pad($prestamo->id, 6, '0', STR_PAD_LEFT).'-'.now()->format('Y');
            $prestamo->save();

            return $prestamo->load(['cliente','user']);
        });
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        $data = $request->validate([
            'fecha_limite'   => 'nullable|date',
            'peso'           => 'required|numeric|min:0',
            'valor_prestado' => 'required|numeric|min:0',
            'interes'        => 'required|numeric|min:0',
            'celular'        => 'nullable|string',
            'detalle'        => 'nullable|string',
            'estado'         => 'nullable|string'
        ]);

        return DB::transaction(function () use ($prestamo, $data) {
            $precioOro = optional(Cog::find(1))->value ?? 0;
            $valorTotal = ($data['peso'] ?? 0) * $precioOro;

            // pagos activos
            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');
            $saldo = ($data['valor_prestado'] + $data['interes']) - $pagado;

            $prestamo->update([
                'fecha_limite'   => $data['fecha_limite'] ?? $prestamo->fecha_limite,
                'peso'           => $data['peso'],
                'precio_oro'     => $precioOro,
                'valor_total'    => $valorTotal,
                'valor_prestado' => $data['valor_prestado'],
                'interes'        => $data['interes'],
                'saldo'          => $saldo,
                'celular'        => $data['celular'] ?? $prestamo->celular,
                'detalle'        => $data['detalle'] ?? $prestamo->detalle,
                'estado'         => $data['estado'] ?? $prestamo->estado,
            ]);

            return $prestamo->load(['cliente','user']);
        });
    }

    public function destroy(Prestamo $prestamo)
    {
        $prestamo->delete();
        return response()->json(['message' => 'Préstamo eliminado']);
    }

    /* ======= PAGOS ======= */

    public function pagos(Prestamo $prestamo)
    {
        return $prestamo->pagos()->with('user')->orderBy('id','desc')->get();
    }

    public function pagar(Request $request)
    {
        $data = $request->validate([
            'prestamo_id' => 'required|integer|exists:prestamos,id',
            'fecha'       => 'nullable|date',
            'monto'       => 'required|numeric|min:0.01',
            'user_id'     => 'required|integer'
        ]);

        return DB::transaction(function () use ($data) {
            $prestamo = Prestamo::findOrFail($data['prestamo_id']);

            $pago = \App\Models\PrestamoPago::create([
                'fecha'       => $data['fecha'] ?? now()->toDateString(),
                'user_id'     => $data['user_id'],
                'prestamo_id' => $prestamo->id,
                'monto'       => $data['monto'],
                'estado'      => 'Activo',
            ]);

            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');
            $prestamo->saldo = ($prestamo->valor_prestado + $prestamo->interes) - $pagado;
            if ($prestamo->saldo <= 0) $prestamo->estado = 'Pagado';
            $prestamo->save();

            return $pago->load('user');
        });
    }

    public function anularPago(PrestamoPago $pago)
    {
        return DB::transaction(function () use ($pago) {
            $pago->estado = 'Anulado';
            $pago->save();

            $prestamo = $pago->prestamo()->first();
            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');
            $prestamo->saldo = ($prestamo->valor_prestado + $prestamo->interes) - $pagado;
            if ($prestamo->saldo > 0 && $prestamo->estado === 'Pagado') {
                $prestamo->estado = 'Pendiente';
            }
            $prestamo->save();

            return response()->json(['message' => 'Pago anulado']);
        });
    }
}
