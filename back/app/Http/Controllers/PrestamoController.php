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
        $fi      = $request->query('fecha_inicio');
        $ff      = $request->query('fecha_fin');
        $userId  = $request->query('user_id');
        $estado  = $request->query('estado', 'Todos');
        $search  = $request->query('search');
        $perPage = (int) $request->query('per_page', 12);

        $q = Prestamo::with(['cliente','user'])->orderBy('id', 'desc');

        if ($fi)     $q->whereDate('fecha_creacion', '>=', $fi);
        if ($ff)     $q->whereDate('fecha_creacion', '<=', $ff);
        if ($userId) $q->where('user_id', $userId);
        if ($estado && $estado !== 'Todos') $q->where('estado', $estado);

        if ($search) {
            $s = trim($search);
            $q->where(function ($w) use ($s) {
                $w->where('numero', 'like', "%{$s}%")
                    ->orWhere('detalle', 'like', "%{$s}%")
                    ->orWhereHas('cliente', function ($qc) use ($s) {
                        $qc->where('name', 'like', "%{$s}%")
                            ->orWhere('ci', 'like', "%{$s}%");
                    });
            });
        }

        return $q->paginate($perPage)->appends($request->query());
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
            'valor_prestado' => 'required|numeric|min:0.01',
            'interes'        => 'required|numeric|gte:1|lte:3',  // % 1–3
            'almacen'        => 'required|numeric|gte:1|lte:3',  // % 1–3
            'celular'        => 'nullable|string',
            'detalle'        => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $precioOro   = optional(Cog::find(1))->value ?? 0; // compra
            $valorTotal  = ($data['peso'] ?? 0) * $precioOro;

            $vp = (float) $data['valor_prestado'];
            $i  = (float) $data['interes']; // %
            $a  = (float) $data['almacen']; // %
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);

            $prestamo = Prestamo::create([
                'numero'         => null,
                'fecha_creacion' => $data['fecha_creacion'] ?? now()->toDateString(),
                'fecha_limite'   => $data['fecha_limite'] ?? null,
                'cliente_id'     => $data['cliente_id'],
                'user_id'        => $data['user_id'],
                'peso'           => $data['peso'],
                'precio_oro'     => $precioOro,
                'valor_total'    => $valorTotal,
                'valor_prestado' => $vp,
                'interes'        => $i,        // guardamos %
                'almacen'        => $a,        // guardamos %
                'saldo'          => $deuda,    // total teórico
                'celular'        => $data['celular'] ?? null,
                'detalle'        => $data['detalle'] ?? null,
                'estado'         => 'Pendiente',
            ]);

            $prestamo->numero = 'PR-' . str_pad($prestamo->id, 6, '0', STR_PAD_LEFT) . '-' . now()->format('Y');
            $prestamo->save();

            return $prestamo->load(['cliente','user']);
        });
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        $data = $request->validate([
            'fecha_limite'   => 'nullable|date',
            'peso'           => 'required|numeric|min:0',
            'valor_prestado' => 'required|numeric|min:0.01',
            'interes'        => 'required|numeric|gte:1|lte:3',   // %
            'almacen'        => 'required|numeric|gte:1|lte:3',   // %
            'celular'        => 'nullable|string',
            'detalle'        => 'nullable|string',
            'estado'         => 'nullable|string'
        ]);

        return DB::transaction(function () use ($prestamo, $data) {
            $precioOro  = optional(Cog::find(1))->value ?? 0;
            $valorTotal = ($data['peso'] ?? 0) * $precioOro;

            $vp = (float) $data['valor_prestado'];
            $i  = (float) $data['interes'];   // %
            $a  = (float) $data['almacen'];   // %

            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');
            $deuda  = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);
            $saldo  = $deuda - $pagado;

            $prestamo->update([
                'fecha_limite'   => $data['fecha_limite'] ?? $prestamo->fecha_limite,
                'peso'           => $data['peso'],
                'precio_oro'     => $precioOro,
                'valor_total'    => $valorTotal,
                'valor_prestado' => $vp,
                'interes'        => $i,  // %
                'almacen'        => $a,  // %
                'saldo'          => $saldo,
                'celular'        => $data['celular'] ?? $prestamo->celular,
                'detalle'        => $data['detalle'] ?? $prestamo->detalle,
                'estado'         => $saldo <= 0 ? 'Pagado' : ($data['estado'] ?? $prestamo->estado),
            ]);

            return $prestamo->load(['cliente','user']);
        });
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
//            'user_id'     => 'required|integer'
        ]);
        $user = $request->user();

        return DB::transaction(function () use ($data,$user,$request) {
            $prestamo = Prestamo::findOrFail($data['prestamo_id']);

            PrestamoPago::create([
                'fecha'       => now()->toDateString(), // o $data['fecha'] si se desea
                'user_id'     => $user->id,
                'metodo'      => $request->input('metodo', 'Efectivo'),
                'tipo_pago'   => $request->input('tipo_pago', 'SALDO'), // Abono | Pago Total
                'prestamo_id' => $prestamo->id,
                'monto'       => $data['monto'],
                'estado'      => 'Activo',
            ]);

            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');

            // recalcular base con % actuales
            $vp = (float) $prestamo->valor_prestado;
            $i  = (float) $prestamo->interes;  // %
            $a  = (float) $prestamo->almacen;  // %
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);

            $prestamo->saldo  = $deuda - $pagado;
            if ($prestamo->saldo <= 0) $prestamo->estado = 'Pagado';
            $prestamo->save();

            return $prestamo->pagos()->with('user')->latest()->first();
        });
    }

    public function anularPago(PrestamoPago $pago)
    {
        return DB::transaction(function () use ($pago) {
            $pago->estado = 'Anulado';
            $pago->save();

            $prestamo = $pago->prestamo()->first();
            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');

            $vp = (float) $prestamo->valor_prestado;
            $i  = (float) $prestamo->interes;  // %
            $a  = (float) $prestamo->almacen;  // %
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);

            $prestamo->saldo  = $deuda - $pagado;
            if ($prestamo->saldo > 0 && $prestamo->estado === 'Pagado') {
                $prestamo->estado = 'Pendiente';
            }
            $prestamo->save();

            return response()->json(['message' => 'Pago anulado']);
        });
    }
}
