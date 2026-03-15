<?php

namespace App\Http\Controllers;

use App\Models\Cog;
use App\Models\Joya;
use App\Models\Orden;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class OrdenController extends Controller
{
    public function atrasadas(Request $request)
    {
        $hoy = now()->toDateString();
        $diasMin = max(1, (int) $request->query('dias', 1));
        $userId = $request->query('user_id');
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 24);

        $q = Orden::with(['cliente', 'user'])
            ->where('tipo', 'Orden')
            ->whereNotIn('estado', ['Entregado', 'Cancelada'])
            ->whereNotNull('fecha_entrega')
            ->whereDate('fecha_entrega', '<', $hoy)
            ->whereRaw('DATEDIFF(?, fecha_entrega) >= ?', [$hoy, $diasMin])
            ->select('*')
            ->selectRaw('DATEDIFF(?, fecha_entrega) as dias_retraso', [$hoy])
            ->orderByDesc('dias_retraso')
            ->orderBy('fecha_entrega');

        if ($userId) {
            $q->where('user_id', $userId);
        }

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

    public function joyasDisponiblesVenta(Request $request)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['Administrador', 'Vendedor'], true)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $query = Joya::with(['estucheItem.columna.vitrina'])
            ->whereDoesntHave('ventas', function ($q) {
                $q->where('tipo', 'Venta directa')
                    ->where('estado', '!=', 'Cancelada');
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('vitrina_id')) {
            $query->whereHas('estucheItem.columna', function ($q) use ($request) {
                $q->where('vitrina_id', $request->integer('vitrina_id'));
            });
        }

        if ($request->filled('estuche_id')) {
            $query->where('estuche_id', $request->integer('estuche_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('tipo', 'like', "%{$search}%")
                    ->orWhere('linea', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(function (Joya $joya) {
            $cogName = $joya->tipo === 'Importada' ? 'Joya importada' : 'Precio Venta';
            $valorCog = (float) optional(Cog::where('name', $cogName)->first())->value;

            return [
                'id' => $joya->id,
                'nombre' => $joya->nombre,
                'tipo' => $joya->tipo,
                'linea' => $joya->linea,
                'peso' => $joya->peso,
                'imagen' => $joya->imagen,
                'monto_bs' => $joya->monto_bs,
                'estuche_id' => $joya->estuche_id,
                'estuche_nombre' => $joya->estucheItem?->nombre,
                'columna_codigo' => $joya->estucheItem?->columna?->codigo,
                'vitrina_nombre' => $joya->estucheItem?->columna?->vitrina?->nombre,
                'precio_configuracion_nombre' => $cogName,
                'precio_configuracion_valor' => $valorCog,
                'precio_referencial' => $this->precioVentaJoya($joya),
            ];
        });
    }

    public function garantia(Orden $orden)
    {
        $orden->load(['cliente', 'user']);

        $empresa = [
            'nombre' => 'JOYERIA ROSARIO',
            'sucursal' => 'ORURO',
            'direccion' => 'Calle Junin esq. La Plata, frente al mercado',
            'cel' => '704-12345',
        ];

        $garantia = [
            'codigo' => $orden->numero,
            'fecha' => now(),
            'cliente' => $orden->cliente->name ?? 'N/A',
            'tipo' => 'Joya',
            'periodo' => '1 ano',
            'detalle' => $orden->detalle,
            'mantenimiento_meses' => 12,
        ];

        $precioOro = 0;
        if (DB::getSchemaBuilder()->hasTable('cogs')) {
            $cog = DB::table('cogs')->where('id', 2)->first();
            $precioOro = $cog ? ($cog->value ?? $cog->valor ?? 0) : 0;
        }

        $pdf = Pdf::loadView('pdf.garantia', [
            'empresa' => $empresa,
            'garantia' => $garantia,
            'precioOro' => $precioOro,
            'hoy' => now(),
        ])->setPaper('A4', 'portrait');

        return $pdf->stream('garantia_'.$orden->numero.'.pdf');
    }

    public function cancelar(Request $request, Orden $orden)
    {
        if ($orden->estado === 'Cancelada') {
            return response()->json(['message' => 'La orden ya esta cancelada'], 409);
        }

        if ($orden->estado === 'Entregado' && $orden->tipo !== 'Venta directa') {
            return response()->json(['message' => 'No se puede cancelar una orden entregada'], 422);
        }

        DB::transaction(function () use ($orden, $request) {
            $orden->estado = 'Cancelada';

            if ($request->boolean('anular_pagos')) {
                $orden->pagos()->where('estado', 'Activo')->update(['estado' => 'Anulado']);
                $nuevoAdelanto = $orden->pagos()->where('estado', 'Activo')->sum('monto');
                $orden->adelanto = $nuevoAdelanto;
                $orden->saldo = max(0, ($orden->costo_total ?? 0) - $nuevoAdelanto);
            }

            $orden->save();
        });

        return response()->json($orden->fresh(['cliente', 'user', 'joya.estucheItem.columna.vitrina']));
    }

    public function show(Orden $orden)
    {
        return Orden::with([
            'cliente:id,name,ci,status,cellphone',
            'user:id,name',
            'joya.estucheItem.columna.vitrina',
        ])->findOrFail($orden->id);
    }

    public function index(Request $request)
    {
        $query = Orden::with(['user:id,name', 'cliente:id,name,ci', 'joya.estucheItem.columna.vitrina'])
            ->orderByDesc('fecha_creacion');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->has('user_id') && $request->user_id !== null && $request->user_id !== 'null') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', (int) $request->cliente_id);
        }

        if ($request->filled('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_creacion', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_creacion', '<=', $request->input('fecha_fin'));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('numero', 'like', "%{$s}%")
                    ->orWhere('detalle', 'like', "%{$s}%")
                    ->orWhereHas('joya', function ($qj) use ($s) {
                        $qj->where('nombre', 'like', "%{$s}%");
                    })
                    ->orWhereHas('cliente', function ($qc) use ($s) {
                        $qc->where('name', 'like', "%{$s}%")
                            ->orWhere('ci', 'like', "%{$s}%")
                            ->orWhere('cellphone', 'like', "%{$s}%");
                    });
            });
        }

        $perPage = $request->integer('per_page', 100);

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $isVentaDirecta = $request->input('tipo') === 'Venta directa';

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'costo_total' => 'nullable|numeric|min:0',
            'adelanto' => 'nullable|numeric|min:0',
            'saldo' => 'nullable|numeric|min:0',
            'tipo_pago' => 'nullable|in:Efectivo,QR',
            'joya_id' => $isVentaDirecta ? 'required|exists:joyas,id' : 'nullable|exists:joyas,id',
            'foto_modelo' => 'nullable|image|max:5120',
        ]);

        $user = $request->user();
        $payload = [
            'numero' => $this->numeroGet($isVentaDirecta ? 'V' : 'O'),
            'tipo' => $isVentaDirecta ? 'Venta directa' : 'Orden',
            'fecha_creacion' => now(),
            'user_id' => $user->id,
            'cliente_id' => $request->input('cliente_id'),
            'celular' => $request->input('celular'),
            'tipo_pago' => $request->input('tipo_pago', 'Efectivo'),
            'kilates18' => $request->input('kilates18'),
            'nota' => $request->input('nota', ''),
            'detalle' => $request->input('detalle', ''),
        ];

        if ($request->hasFile('foto_modelo')) {
            $payload['foto_modelo'] = $this->storeOrdenImage($request->file('foto_modelo'));
        }

        if ($isVentaDirecta) {
            $joya = Joya::with(['estucheItem.columna.vitrina'])->findOrFail($request->integer('joya_id'));
            $ocupada = Orden::where('joya_id', $joya->id)
                ->where('tipo', 'Venta directa')
                ->where('estado', '!=', 'Cancelada')
                ->exists();

            if ($ocupada) {
                return response()->json(['message' => 'La joya seleccionada ya tiene una venta registrada'], 422);
            }

            $costoTotal = (float) ($request->input('costo_total') ?: $this->precioVentaJoya($joya));
            $adelanto = (float) $request->input('adelanto', 0);

            $payload = array_merge($payload, [
                'joya_id' => $joya->id,
                'fecha_entrega' => $request->input('fecha_entrega') ?: now()->toDateString(),
                'detalle' => $request->input('detalle') ?: sprintf(
                    'VENTA DIRECTA: %s | %s | %s GR',
                    $joya->nombre,
                    $joya->tipo,
                    number_format((float) $joya->peso, 2, '.', '')
                ),
                'peso' => $request->input('peso', $joya->peso),
                'costo_total' => $costoTotal,
                'adelanto' => $adelanto,
                'saldo' => max(0, $costoTotal - $adelanto),
                'estado' => $costoTotal - $adelanto <= 0 ? 'Entregado' : 'Pendiente',
            ]);
        } else {
            $costoTotal = (float) $request->input('costo_total', 0);
            $adelanto = (float) $request->input('adelanto', 0);

            $payload = array_merge($payload, [
                'fecha_entrega' => $request->input('fecha_entrega'),
                'peso' => $request->input('peso', 0),
                'costo_total' => $costoTotal,
                'adelanto' => $adelanto,
                'saldo' => max(0, (float) $request->input('saldo', $costoTotal - $adelanto)),
                'estado' => $request->input('estado', $costoTotal - $adelanto <= 0 ? 'Entregado' : 'Pendiente'),
            ]);
        }

        return Orden::create($payload)->load(['cliente:id,name,ci,status,cellphone', 'user:id,name', 'joya.estucheItem.columna.vitrina']);
    }

    public function pagarTodo(Request $request, Orden $orden)
    {
        $metodo = $request->input('metodo', 'EFECTIVO');
        $saldo = (($orden->costo_total ?? 0) - $orden->pagos()->where('estado', 'Activo')->sum('monto')) - $orden->adelanto;

        $orden->pagos()->create([
            'monto' => $saldo,
            'metodo' => $metodo,
            'estado' => 'Activo',
            'fecha' => now(),
            'user_id' => $request->user()->id ?? null,
        ]);

        $orden->saldo = 0;
        $orden->save();

        if ($orden->saldo <= 0) {
            $orden->estado = 'Entregado';
            $orden->save();
        }

        return $orden->fresh(['cliente', 'user', 'joya.estucheItem.columna.vitrina']);
    }

    public function toggleMetodo(Request $request, Orden $orden)
    {
        $user = $request->user();
        if (! $user || ($user->role ?? null) !== 'Administrador') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (($orden->estado ?? 'Pendiente') === 'Cancelada') {
            return response()->json(['message' => 'No se puede cambiar el metodo de una orden cancelada'], 422);
        }

        if ((float) ($orden->adelanto ?? 0) <= 0) {
            return response()->json(['message' => 'La orden no tiene adelanto para cambiar de metodo'], 422);
        }

        $actual = strtoupper((string) $orden->tipo_pago);
        $nuevoMetodo = $actual === 'QR' ? 'Efectivo' : 'QR';

        $orden->update(['tipo_pago' => $nuevoMetodo]);

        return response()->json($orden->fresh(['cliente', 'user', 'joya.estucheItem.columna.vitrina']));
    }

    public function update(Request $request, Orden $orden)
    {
        $data = $request->all();
        unset($data['cliente_id']);

        $user = $request->user();
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'administrador', 'administrator'], true);

        if (! $isAdmin) {
            unset($data['costo_total'], $data['peso'], $data['estado'], $data['joya_id'], $data['tipo']);
        }

        $orden->update($data);

        $pagado = $orden->pagos()->where('estado', 'Activo')->sum('monto');
        $orden->saldo = (float) $request->input('costo_total', $orden->costo_total) - ((float) $request->input('adelanto', $orden->adelanto) + $pagado);
        $orden->save();

        return $orden->load(['cliente', 'user', 'joya.estucheItem.columna.vitrina']);
    }

    public function destroy(Orden $orden)
    {
        $orden->delete();

        return response()->json(['message' => 'Orden eliminada']);
    }

    public function pdf(Orden $orden)
    {
        $orden->load(['cliente', 'user']);

        $precioOro = 0;
        try {
            $cog = DB::table('cogs')->where('id', 2)->first();
            $precioOro = $cog ? ($cog->value ?? $cog->valor ?? 0) : 0;
        } catch (\Throwable $e) {
            $precioOro = 0;
        }

        $empresa = [
            'nombre' => 'Joyeria Rosario',
            'sucursal' => 'ORURO',
            'direccion' => 'Adolfo mier entre potosi y pagador (Lado palace Hotel)',
            'cel' => '7380504',
            'nit' => '',
        ];

        $pagosActivos = $orden->pagos()->where('estado', 'Activo')->sum('monto');
        $orden->adelanto = $orden->adelanto + $pagosActivos;

        $pdf = Pdf::loadView('pdf.orden_trabajo', [
            'orden' => $orden,
            'empresa' => $empresa,
            'precioOro' => $precioOro,
            'hoy' => now(),
        ])->setPaper('letter', 'portrait');

        $fileName = 'orden_trabajo_'.$orden->numero.'.pdf';

        return $pdf->download($fileName);
    }

    private function numeroGet(string $prefix = 'O'): string
    {
        $year = date('Y');
        $ultimoNumero = Orden::orderByDesc('id')
            ->where('numero', 'like', $prefix.'%'.'-'.$year)
            ->first();

        if ($ultimoNumero) {
            $numero = $ultimoNumero->numero;
            $numeroPartes = explode('-', $numero);
            $numeroSecuencia = (int) substr($numeroPartes[0], 1, 4) + 1;

            return $prefix.str_pad((string) $numeroSecuencia, 4, '0', STR_PAD_LEFT).'-'.$year;
        }

        return $prefix.'0001-'.$year;
    }

    private function precioVentaJoya(Joya $joya): float
    {
        $cogName = $joya->tipo === 'Importada' ? 'Joya importada' : 'Precio Venta';
        $valorCog = (float) optional(Cog::where('name', $cogName)->first())->value;

        if ($valorCog > 0 && (float) $joya->peso > 0) {
            return round((float) $joya->peso * $valorCog, 2);
        }

        return round((float) ($joya->monto_bs ?? 0), 2);
    }

    private function storeOrdenImage($file): string
    {
        $filename = time().'_'.uniqid('orden_', true).'.jpg';
        $path = public_path('images/'.$filename);

        $manager = new ImageManager(new Driver);
        $manager->read($file->getPathname())
            ->cover(900, 900)
            ->toJpeg(78)
            ->save($path);

        return $filename;
    }
}
