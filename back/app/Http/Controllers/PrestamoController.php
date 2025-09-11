<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\PrestamoPago;
use App\Models\Cog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    public function retrasados(Request $request)
    {
        $hoy      = now()->toDateString();
        $diasMin  = max(1, (int) $request->query('dias', 1));
        $userId   = $request->query('user_id');
        $search   = trim((string) $request->query('search', ''));
        $perPage  = (int) $request->query('per_page', 24);

        $q = Prestamo::with(['cliente','user'])
            ->whereNotIn('estado', ['Pagado','Cancelado'])
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', $hoy)
            ->whereRaw('DATEDIFF(?, fecha_limite) >= ?', [$hoy, $diasMin])
            ->select('*')
            ->selectRaw('DATEDIFF(?, fecha_limite) as dias_retraso', [$hoy])
            ->orderByDesc('dias_retraso')
            ->orderBy('fecha_limite');

        if ($userId) $q->where('user_id', $userId);

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('numero', 'like', "%{$search}%")
                    ->orWhere('detalle', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($qc) use ($search) {
                        $qc->where('name', 'like', "%{$search}%")
                            ->orWhere('ci', 'like', "%{$search}%");
                    });
            });
        }

        return $q->paginate($perPage)->appends($request->query());
    }

    public function index(Request $request)
    {
        $fi      = $request->query('fecha_inicio');
        $ff      = $request->query('fecha_fin');
        $userId  = $request->query('user_id');
        $estado  = $request->query('estado', 'Todos');
        $search  = $request->query('search');
        $perPage = (int) $request->query('per_page', 12);

        $q = Prestamo::with(['cliente','user'])->orderBy('id', 'desc');

//        if ($fi)     $q->whereDate('fecha_creacion', '>=', $fi);
//        if ($ff)     $q->whereDate('fecha_creacion', '<=', $ff);
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
            'user_id'        => 'required|integer|exists:users,id',
            'fecha_creacion' => 'nullable|date',
            'fecha_limite'   => 'nullable|date',
            'peso'           => 'required|numeric|min:0',  // kg (bruto)
            'merma'          => 'required|numeric|min:0',  // kg
            'valor_prestado' => 'required|numeric|min:0.01',
            'interes'        => 'required|numeric|gte:1|lte:3',  // %
            'almacen'        => 'required|numeric|gte:1|lte:3',  // %
            'celular'        => 'nullable|string',
            'detalle'        => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            // Precio COMPRA oro (coincide con front que usa cogs/3)
            $precioOro = optional(Cog::find(3))->value ?? 0;

            // Peso neto con merma
            $pesoBruto = (float) $data['peso'];
            $merma     = (float) $data['merma'];
            $pesoNeto  = max(0, $pesoBruto - $merma);

            // Valor referencial por peso NETO
            $valorTotal = round($pesoNeto * (float)$precioOro, 2);

            // Deuda teórica según % (sobre lo prestado)
            $vp = (float) $data['valor_prestado'];
            $i  = (float) $data['interes']; // %
            $a  = (float) $data['almacen']; // %
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);

            $prestamo = Prestamo::create([
                'numero'         => null,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'fecha_limite'   => $data['fecha_limite'] ?? null,
                'cliente_id'     => $data['cliente_id'],
                'user_id'        => $data['user_id'],
                'peso'           => $pesoBruto,
                'merma'          => $merma,
                'peso_neto'      => $pesoNeto,    // opcional si tienes la columna
                'precio_oro'     => $precioOro,
                'valor_total'    => $valorTotal,
                'valor_prestado' => $vp,
                'interes'        => $i,          // %
                'almacen'        => $a,          // %
                'saldo'          => $deuda,      // total teórico
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
        // Validación base (permitimos fecha_creacion para pruebas)
        $data = $request->validate([
            'fecha_creacion' => 'nullable|date',
            'fecha_limite'   => 'nullable|date',
            'peso'           => 'nullable|numeric|min:0',   // kg (bruto)
            'merma'          => 'nullable|numeric|min:0',   // kg
            'valor_prestado' => 'nullable|numeric|min:0.01',
            'interes'        => 'nullable|numeric|gte:1|lte:3',   // %
            'almacen'        => 'nullable|numeric|gte:1|lte:3',   // %
            'celular'        => 'nullable|string',
            'detalle'        => 'nullable|string',
            'estado'         => 'nullable|string'
        ]);

        $user = $request->user();
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin','administrador','administrator']);

        return DB::transaction(function () use ($prestamo, $data, $isAdmin) {
            // Precio compra oro (cogs id=3) para valor_total referencial
            $precioOro = optional(Cog::find(3))->value ?? 0;

            // Tomamos actuales como base
            $pesoBruto = array_key_exists('peso', $data)   ? (float)$data['peso']   : (float)($prestamo->peso ?? 0);
            $merma     = array_key_exists('merma', $data)  ? (float)$data['merma']  : (float)($prestamo->merma ?? 0);

            // Si NO es admin, NO puede tocar campos sensibles:
            if (!$isAdmin) {
                unset($data['peso'], $data['merma'], $data['valor_prestado'], $data['interes'], $data['almacen'], $data['estado'], $data['fecha_creacion']);
                // Re-usa los existentes para cálculo
                $pesoBruto = (float)($prestamo->peso ?? 0);
                $merma     = (float)($prestamo->merma ?? 0);
            }

            $pesoNeto = max(0, $pesoBruto - $merma);
            $valorTotal = round($pesoNeto * (float)$precioOro, 2);

            // Si vienen los valores, úsalos; si no, toma actuales
            $vp = array_key_exists('valor_prestado', $data) ? (float)$data['valor_prestado'] : (float)$prestamo->valor_prestado;
            $i  = array_key_exists('interes', $data)        ? (float)$data['interes']        : (float)$prestamo->interes;
            $a  = array_key_exists('almacen', $data)        ? (float)$data['almacen']        : (float)$prestamo->almacen;

            // Pagos activos (para coherencia en respuesta; el modelo recalcula saldo dinámico al leer)
            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');
            $deuda  = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);
            $saldo  = $deuda - $pagado;

            // Armamos payload final de actualización
            $update = [
                'fecha_limite'   => $data['fecha_limite'] ?? $prestamo->fecha_limite,
                'precio_oro'     => $precioOro,
                'valor_total'    => $valorTotal,
                'celular'        => $data['celular'] ?? $prestamo->celular,
                'detalle'        => $data['detalle'] ?? $prestamo->detalle,
            ];

            if ($isAdmin) {
                $update = array_merge($update, [
                    'fecha_creacion' => $data['fecha_creacion'] ?? $prestamo->fecha_creacion,
                    'peso'           => $pesoBruto,
                    'merma'          => $merma,
                    'peso_neto'      => $pesoNeto,
                    'valor_prestado' => $vp,
                    'interes'        => $i,
                    'almacen'        => $a,
                    // El estado sólo lo dejamos cambiar manualmente si viene y si es admin;
                    // de lo contrario lo derivamos por saldo
                    'estado'         => array_key_exists('estado', $data)
                        ? $data['estado']
                        : ($saldo <= 0 ? 'Pagado' : $prestamo->estado),
                ]);
            }

            $prestamo->update($update);

            // Para consistencia inmediata en la respuesta:
            $prestamo->saldo = $saldo; // El accessor del modelo volverá a calcular en lecturas futuras
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
        ]);
        $user = $request->user();

        return DB::transaction(function () use ($data,$user,$request) {
            $prestamo = Prestamo::findOrFail($data['prestamo_id']);

            PrestamoPago::create([
                'fecha'       => now()->toDateString(), // o $data['fecha']
                'user_id'     => $user->id,
                'metodo'      => $request->input('metodo', 'Efectivo'),
                'tipo_pago'   => $request->input('tipo_pago', 'SALDO'), // Abono | Pago Total
                'prestamo_id' => $prestamo->id,
                'monto'       => $data['monto'],
                'estado'      => 'Activo',
            ]);

            $pagado = $prestamo->pagos()->where('estado','Activo')->sum('monto');

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
