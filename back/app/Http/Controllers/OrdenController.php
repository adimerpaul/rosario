<?php

namespace App\Http\Controllers;

use App\Models\Cog;
use App\Models\Egreso;
use App\Models\Joya;
use App\Models\Orden;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $this->authorizeVentasAccess($request);

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

        if ($request->filled('linea') && $request->input('linea') !== 'Todos') {
            $query->where('linea', $request->input('linea'));
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

    public function joyasVitrina(Request $request)
    {
        $this->authorizeVentasAccess($request);

        $perPage = max(1, $request->integer('per_page', 12));
        $page = max(1, $request->integer('page', 1));
        $search = trim((string) $request->input('search', ''));
        $estadoJoya = $request->input('estado_joya');

        $joyas = Joya::with([
            'user:id,name',
            'estucheItem.columna.vitrina',
            'ventas' => function ($query) {
                $query->where('tipo', 'Venta directa')
                    ->with(['cliente:id,name,ci', 'user:id,name'])
                    ->orderByDesc('id');
            },
        ])
            ->when($request->filled('linea') && $request->input('linea') !== 'Todos', function ($query) use ($request) {
                $query->where('linea', $request->input('linea'));
            })
            ->when($request->filled('vitrina_id'), function ($query) use ($request) {
                $query->whereHas('estucheItem.columna', function ($subQuery) use ($request) {
                    $subQuery->where('vitrina_id', $request->integer('vitrina_id'));
                });
            })
            ->when($request->filled('estuche_id'), function ($query) use ($request) {
                $query->where('estuche_id', $request->integer('estuche_id'));
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhere('linea', 'like', "%{$search}%")
                        ->orWhere('estuche', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Joya $joya) => $this->mapJoyaVitrina($joya))
            ->filter(function (array $joya) use ($estadoJoya, $request) {
                if ($request->has('user_id') && $request->input('user_id') !== null && $request->input('user_id') !== 'null') {
                    if ((int) ($joya['user_id'] ?? 0) !== (int) $request->input('user_id')) {
                        return false;
                    }
                }

                if ($request->filled('fecha') && ! empty($joya['fecha_referencia'])) {
                    if (Carbon::parse($joya['fecha_referencia'])->toDateString() !== $request->input('fecha')) {
                        return false;
                    }
                }

                if (! $estadoJoya || $estadoJoya === 'Todos') {
                    return true;
                }

                return $joya['estado_joya'] === $estadoJoya;
            })
            ->values();

        $items = $joyas->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $joyas->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
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
            $adelantoActual = (float) ($orden->adelanto ?? 0);
            $pagosActivos = $orden->pagos()->where('estado', 'Activo')->get();
            $totalPagosActivos = (float) $pagosActivos->sum('monto');

            $orden->estado = 'Cancelada';

            if ($request->boolean('anular_pagos')) {
                $orden->pagos()->where('estado', 'Activo')->update(['estado' => 'Anulado']);
                $orden->adelanto = 0;
                $orden->saldo = max(0, (float) ($orden->costo_total ?? 0));
            } else {
                $orden->saldo = max(0, (float) ($orden->costo_total ?? 0) - $adelantoActual - $totalPagosActivos);
            }

            $orden->save();

            $montoRevertido = $request->boolean('anular_pagos')
                ? $adelantoActual + $totalPagosActivos
                : $adelantoActual;

            if ($montoRevertido > 0) {
                $this->registrarEgresoAnulacionOrden(
                    $request,
                    $orden,
                    $montoRevertido,
                    $this->resolverMetodoAnulacionOrden($orden, $pagosActivos->pluck('metodo')->all())
                );
            }
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
            $tipo = $request->input('tipo');
            $query->where(function ($q) use ($tipo) {
                $q->where('tipo', $tipo);

                if ($tipo === 'Orden') {
                    $q->orWhereNull('tipo')
                        ->orWhere('tipo', '');
                }
            });
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

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_creacion', $request->input('fecha'));
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_creacion', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_creacion', '<=', $request->input('fecha_fin'));
        }

        if ($request->filled('linea') && $request->input('linea') !== 'Todos') {
            $linea = (string) $request->input('linea');
            $query->whereHas('joya', function ($q) use ($linea) {
                $q->where('linea', $linea);
            });
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

    public function ventasReportePdf(Request $request)
    {
        $this->authorizeVentasAccess($request);

        $tipoReporte = (string) $request->input('tipo_reporte', 'detalle');
        $ventas = Orden::with(['joya.estucheItem.columna.vitrina', 'user:id,name', 'cliente:id,name,ci'])
            ->where('tipo', 'Venta directa')
            ->where('estado', '!=', 'Cancelada')
            ->when($request->filled('linea') && $request->input('linea') !== 'Todos', function ($query) use ($request) {
                $linea = (string) $request->input('linea');
                $query->whereHas('joya', function ($joyaQuery) use ($linea) {
                    $joyaQuery->where('linea', $linea);
                });
            })
            ->when($request->filled('fecha'), function ($query) use ($request) {
                $query->whereDate('fecha_creacion', $request->input('fecha'));
            }, function ($query) use ($request) {
                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('fecha_creacion', '>=', $request->input('fecha_inicio'));
                }

                if ($request->filled('fecha_fin')) {
                    $query->whereDate('fecha_creacion', '<=', $request->input('fecha_fin'));
                }
            })
            ->orderBy('fecha_creacion')
            ->orderBy('id')
            ->get()
            ->map(function (Orden $venta) {
                return [
                    'fecha' => $venta->fecha_creacion ? Carbon::parse($venta->fecha_creacion)->format('d/m/Y') : '',
                    'codigo' => $venta->numero,
                    'detalle' => $venta->joya?->nombre ?: $venta->detalle,
                    'peso' => (float) ($venta->joya?->peso ?? $venta->peso ?? 0),
                    'monto' => (float) ($venta->costo_total ?? 0),
                    'usuario' => $venta->user?->name ?: 'SIN USUARIO',
                    'linea' => $this->lineaLabel($venta->joya?->linea),
                ];
            })
            ->values();

        $pdf = Pdf::loadView('pdf.ventas_resumen', [
            'titulo' => $tipoReporte === 'todas' ? 'DETALLE DE TODAS LAS VENTAS' : 'DETALLE DE VENTAS',
            'fechaInicio' => $request->input('fecha') ?: $request->input('fecha_inicio'),
            'fechaFin' => $request->input('fecha') ?: $request->input('fecha_fin'),
            'linea' => $this->lineaLabel($request->input('linea')),
            'ventas' => $ventas,
            'total' => $ventas->sum('monto'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download('reporte_ventas_joyas.pdf');
    }

    public function inventarioExistenciasPdf(Request $request)
    {
        $this->authorizeVentasAccess($request);

        $joyas = $this->inventarioDisponibleQuery($request)
            ->orderBy('linea')
            ->orderBy('nombre')
            ->get()
            ->map(function (Joya $joya) {
                return [
                    'codigo' => $this->joyaCodigo($joya->id),
                    'imagen' => $joya->imagen,
                    'detalle' => $joya->nombre,
                    'peso' => (float) ($joya->peso ?? 0),
                    'linea' => $this->lineaLabel($joya->linea),
                    'usuario' => $joya->user?->name ?: 'SIN USUARIO',
                ];
            })
            ->values();

        $pdf = Pdf::loadView('pdf.inventario_existencias', [
            'linea' => $this->lineaLabel($request->input('linea')),
            'estuche' => $this->resolveEstucheNombre($request),
            'joyas' => $joyas,
        ])->setPaper('letter', 'portrait');

        return $pdf->download('inventario_existente_joyas.pdf');
    }

    public function inventarioMovimientosPdf(Request $request)
    {
        $this->authorizeVentasAccess($request);

        $movimientos = $this->buildInventarioMovimientos($request);

        $pdf = Pdf::loadView('pdf.inventario_movimientos', [
            'fechaInicio' => $request->input('fecha') ?: $request->input('fecha_inicio'),
            'fechaFin' => $request->input('fecha') ?: $request->input('fecha_fin'),
            'linea' => $this->lineaLabel($request->input('linea')),
            'estuche' => $this->resolveEstucheNombre($request),
            'movimientos' => $movimientos,
        ])->setPaper('letter', 'portrait');

        return $pdf->download('movimientos_inventario_joyas.pdf');
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
        $request->validate([
            'foto_modelo' => 'nullable|image|max:5120',
        ]);

        $data = $request->except(['cliente_id']);

        $user = $request->user();
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin', 'administrador', 'administrator'], true);

        if (! $isAdmin) {
            unset($data['costo_total'], $data['peso'], $data['estado'], $data['joya_id'], $data['tipo']);
        }

        if (! $isAdmin || $request->has('costo_total')) {
            unset($data['adelanto']);
        }

        if ($request->hasFile('foto_modelo')) {
            $data['foto_modelo'] = $this->storeOrdenImage($request->file('foto_modelo'));
        } else {
            unset($data['foto_modelo']);
        }

        $orden->update($data);

        $pagado = $orden->pagos()->where('estado', 'Activo')->sum('monto');
        $orden->saldo = max(0, (float) ($orden->costo_total ?? 0) - ((float) ($orden->adelanto ?? 0) + $pagado));

        if ($orden->estado !== 'Cancelada') {
            $orden->estado = $orden->saldo <= 0 ? 'Entregado' : 'Pendiente';
        }

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

    private function registrarEgresoAnulacionOrden(Request $request, Orden $orden, float $monto, ?string $metodo = null): void
    {
        Egreso::create([
            'fecha' => now()->toDateString(),
            'descripcion' => 'ANULACION ORDEN '.$orden->numero,
            'metodo' => $this->normalizarMetodoCaja($metodo),
            'monto' => round($monto, 2),
            'estado' => 'Activo',
            'user_id' => $request->user()?->id,
            'nota' => 'Reversion automatica de ingresos al cancelar la orden',
        ]);
    }

    private function resolverMetodoAnulacionOrden(Orden $orden, array $metodosPagos = []): string
    {
        $metodos = collect($metodosPagos)
            ->prepend($orden->tipo_pago)
            ->filter(fn ($metodo) => filled($metodo))
            ->values();

        return (string) ($metodos->first() ?: 'EFECTIVO');
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

    private function authorizeVentasAccess(Request $request): void
    {
        $role = $request->user()?->role;

        abort_unless(in_array($role, ['Administrador', 'Vendedor'], true), 403, 'No autorizado');
    }

    private function inventarioDisponibleQuery(Request $request)
    {
        return Joya::with(['user:id,name', 'estucheItem.columna.vitrina'])
            ->whereDoesntHave('ventas', function ($query) {
                $query->where('tipo', 'Venta directa')
                    ->where('estado', '!=', 'Cancelada');
            })
            ->when($request->filled('linea') && $request->input('linea') !== 'Todos', function ($query) use ($request) {
                $query->where('linea', $request->input('linea'));
            })
            ->when($request->filled('estuche_id'), function ($query) use ($request) {
                $query->where('estuche_id', $request->integer('estuche_id'));
            });
    }

    private function buildInventarioMovimientos(Request $request): Collection
    {
        $joyaAudits = DB::table('audits')
            ->where('auditable_type', Joya::class)
            ->whereIn('event', ['created', 'updated', 'deleted'])
            ->orderBy('created_at')
            ->get();

        $users = User::whereIn('id', $joyaAudits->pluck('user_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $joyas = Joya::withTrashed()
            ->with(['estucheItem.columna.vitrina'])
            ->get()
            ->keyBy('id');

        $movimientosJoyas = $joyaAudits->map(function ($audit) use ($users, $joyas, $request) {
            $oldValues = $this->decodeAuditValues($audit->old_values);
            $newValues = $this->decodeAuditValues($audit->new_values);
            $joya = $joyas->get((int) $audit->auditable_id);
            $linea = $newValues['linea'] ?? $oldValues['linea'] ?? $joya?->linea;
            $estuche = $newValues['estuche'] ?? $oldValues['estuche'] ?? $joya?->estuche;

            if (! $this->matchesLineaFilter($linea, $request) || ! $this->matchesEstucheFilter($estuche, $request)) {
                return null;
            }

            $estado = null;
            if ($audit->event === 'created') {
                $estado = 'INGRESO INVENTARIO';
            } elseif ($audit->event === 'deleted') {
                $estado = 'ELIMINADO';
            } elseif (
                array_key_exists('estuche_id', $newValues)
                || array_key_exists('estuche', $newValues)
                || array_key_exists('linea', $newValues)
            ) {
                $estado = 'CAMBIO DE ESTUCHE';
            }

            if (! $estado) {
                return null;
            }

            $fecha = Carbon::parse($audit->created_at);
            if (! $this->matchesDateFilter($fecha, $request)) {
                return null;
            }

            return [
                'fecha' => $fecha->format('d/m/Y'),
                'codigo' => $this->joyaCodigo((int) $audit->auditable_id),
                'detalle' => $newValues['nombre'] ?? $oldValues['nombre'] ?? $joya?->nombre ?? 'SIN DETALLE',
                'peso' => (float) ($newValues['peso'] ?? $oldValues['peso'] ?? $joya?->peso ?? 0),
                'linea' => $this->lineaLabel($linea),
                'estado' => $estado,
                'usuario' => $users[$audit->user_id] ?? 'SISTEMA',
                'timestamp' => $fecha->timestamp,
            ];
        })->filter();

        $movimientosVentas = Orden::with(['joya' => fn ($query) => $query->withTrashed()->with(['estucheItem.columna.vitrina']), 'user:id,name'])
            ->where('tipo', 'Venta directa')
            ->where('estado', '!=', 'Cancelada')
            ->when($request->filled('linea') && $request->input('linea') !== 'Todos', function ($query) use ($request) {
                $query->whereHas('joya', function ($joyaQuery) use ($request) {
                    $joyaQuery->where('linea', $request->input('linea'));
                });
            })
            ->when($request->filled('estuche_id'), function ($query) use ($request) {
                $query->whereHas('joya', function ($joyaQuery) use ($request) {
                    $joyaQuery->where('estuche_id', $request->integer('estuche_id'));
                });
            })
            ->get()
            ->filter(function (Orden $venta) use ($request) {
                return $this->matchesDateFilter(Carbon::parse($venta->fecha_creacion), $request);
            })
            ->map(function (Orden $venta) {
                $fecha = Carbon::parse($venta->fecha_creacion);

                return [
                    'fecha' => $fecha->format('d/m/Y'),
                    'codigo' => $this->joyaCodigo((int) $venta->joya_id),
                    'detalle' => $venta->joya?->nombre ?: $venta->detalle,
                    'peso' => (float) ($venta->joya?->peso ?? $venta->peso ?? 0),
                    'linea' => $this->lineaLabel($venta->joya?->linea),
                    'estado' => 'VENDIDO',
                    'usuario' => $venta->user?->name ?: 'SIN USUARIO',
                    'timestamp' => $fecha->timestamp,
                ];
            });

        return $movimientosJoyas
            ->merge($movimientosVentas)
            ->sortBy('timestamp')
            ->values();
    }

    private function decodeAuditValues($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function matchesDateFilter(Carbon $fecha, Request $request): bool
    {
        if ($request->filled('fecha')) {
            return $fecha->toDateString() === $request->input('fecha');
        }

        if ($request->filled('fecha_inicio') && $fecha->toDateString() < $request->input('fecha_inicio')) {
            return false;
        }

        if ($request->filled('fecha_fin') && $fecha->toDateString() > $request->input('fecha_fin')) {
            return false;
        }

        return true;
    }

    private function matchesLineaFilter(?string $linea, Request $request): bool
    {
        if (! $request->filled('linea') || $request->input('linea') === 'Todos') {
            return true;
        }

        return $linea === $request->input('linea');
    }

    private function matchesEstucheFilter(?string $estuche, Request $request): bool
    {
        if (! $request->filled('estuche_id')) {
            return true;
        }

        $nombreEstuche = $this->resolveEstucheNombre($request);

        return $nombreEstuche !== 'TODOS' && $estuche === $nombreEstuche;
    }

    private function resolveEstucheNombre(Request $request): string
    {
        if (! $request->filled('estuche_id')) {
            return 'TODOS';
        }

        return (string) DB::table('estuches')
            ->where('id', $request->integer('estuche_id'))
            ->value('nombre') ?: 'TODOS';
    }

    private function lineaLabel(?string $linea): string
    {
        if (! $linea || $linea === 'Todos') {
            return 'TODOS';
        }

        return $linea === 'Andreina' ? 'REINA' : strtoupper($linea);
    }

    private function joyaCodigo(int $id): string
    {
        return 'J'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function mapJoyaVitrina(Joya $joya): array
    {
        /** @var \App\Models\Orden|null $ultimaVenta */
        $ultimaVenta = $joya->ventas->first();
        $estadoJoya = $this->estadoJoyaVitrina($ultimaVenta);
        $precioReferencial = $this->precioVentaJoya($joya);
        $fechaReferencia = $ultimaVenta?->fecha_creacion ?: $joya->created_at;

        return [
            'id' => $joya->id,
            'venta_id' => $ultimaVenta?->id,
            'numero' => $ultimaVenta?->numero ?: $this->joyaCodigo($joya->id),
            'estado_joya' => $estadoJoya,
            'fecha_referencia' => $fechaReferencia,
            'detalle' => $ultimaVenta?->detalle ?: 'DISPONIBLE EN VITRINA',
            'costo_total' => (float) ($ultimaVenta?->costo_total ?? $precioReferencial),
            'adelanto' => (float) ($ultimaVenta?->adelanto ?? 0),
            'saldo' => (float) ($ultimaVenta?->saldo ?? 0),
            'tipo_pago' => $ultimaVenta?->tipo_pago ?: 'Efectivo',
            'cliente' => $ultimaVenta?->cliente ? [
                'name' => $ultimaVenta->cliente->name,
                'ci' => $ultimaVenta->cliente->ci,
            ] : null,
            'user_id' => $ultimaVenta?->user?->id ?: $joya->user?->id,
            'user' => ['name' => $ultimaVenta?->user?->name ?: $joya->user?->name ?: 'SIN USUARIO'],
            'joya' => [
                'id' => $joya->id,
                'nombre' => $joya->nombre,
                'tipo' => $joya->tipo,
                'linea' => $joya->linea,
                'peso' => $joya->peso,
                'imagen' => $joya->imagen,
                'estuche_item' => [
                    'nombre' => $joya->estucheItem?->nombre,
                    'columna' => [
                        'codigo' => $joya->estucheItem?->columna?->codigo,
                        'vitrina' => [
                            'nombre' => $joya->estucheItem?->columna?->vitrina?->nombre,
                        ],
                    ],
                ],
            ],
            'fecha_creacion' => $ultimaVenta?->fecha_creacion,
            'fecha_vitrina' => $joya->created_at,
            'totalPagos' => (float) ($ultimaVenta?->totalPagos ?? 0),
        ];
    }

    private function estadoJoyaVitrina(?Orden $venta): string
    {
        if (! $venta) {
            return 'EN VITRINA';
        }

        if ($venta->estado === 'Cancelada') {
            return 'ANULADO';
        }

        if ($venta->estado === 'Entregado') {
            return 'VENDIDO';
        }

        return 'RESERVADO';
    }
}
