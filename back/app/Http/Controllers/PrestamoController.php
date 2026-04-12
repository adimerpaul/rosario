<?php

namespace App\Http\Controllers;

use App\Models\Cog;
use App\Models\Prestamo;
use App\Models\PrestamoPago;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrestamoController extends Controller
{
    private function precioPrestamoActual(): float
    {
        $cog = Cog::query()
            ->where('name', 'Prestamos para comprar')
            ->first();

        if (! $cog) {
            $cog = Cog::find(3);
        }

        return (float) ($cog?->value ?? 0);
    }

    public function pagoPdf(PrestamoPago $pago)
    {
        $pago->load(['prestamo.cliente', 'user']);

        $empresa = [
            'nombre' => 'Joyería Rosario',
            'direccion' => 'Adolfo Mier entre Potosi y Pagador',
            'ciudad' => 'Oruro',
            'pais' => 'Bolivia',
            'cel' => '73800584',
        ];

        $pdf = Pdf::loadView('pdf.prestamo_pago', [
            'empresa' => $empresa,
            'pago' => $pago,
            'prestamo' => $pago->prestamo,
            'cliente' => $pago->prestamo?->cliente,
            'usuario' => $pago->user,
            'hoy' => now(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('prestamo-pago-'.$pago->id.'.pdf');
    }

    //    function fundir
    // Route::post('prestamos/{prestamo}/fundir', [PrestamoController::class, 'fundir']);
    public function fundir(Request $request, Prestamo $prestamo)
    {
        $prestamo->estado = 'Fundido';
        $prestamo->save();
    }

    public function totalInvertido()
    {
        $valorPestado = Prestamo::whereIn('estado', ['Pendiente', 'Activo', 'Entregado'])->sum('valor_prestado');
        error_log(json_encode($valorPestado));
        $valorAmortizado = PrestamoPago::whereIn('estado', ['Activo'])
            ->whereIn('tipo_pago', ['SALDO', 'TOTAL'])
            ->sum('monto');

        //        'SALDO','CARGOS','TOTAL'
        return $valorPestado - $valorAmortizado;
    }

    public function comprobanteCambioPrestamoPdf(Prestamo $prestamo)
    {
        // Identidad de la agencia (fijo)
        $agencia = 'JOYERÍA ROSARIO';
        $direccion = 'Adolfo Mier entre Potosi y pagador (Lado palace Hotel)';

        // Usuario (cajero) y cliente desde la relación del préstamo
        $usuario = strtoupper($prestamo->user->username ?? $prestamo->user->name ?? 'USUARIO');
        $cuint = '—'; // si no usas este dato
        $cliente = strtoupper($prestamo->cliente->name ?? '—');

        // Fecha/hora (uso la fecha de creación del préstamo si existe)
        $fechaAt = $prestamo->fecha_creacion ? Carbon::parse($prestamo->fecha_creacion) : now();

        // Tipo de cambio desde COG id=4
        $tc = optional(\App\Models\Cog::find(4))->value ?? 6.96;
        if ($tc <= 0) {
            $tc = 6.96;
        }

        // ===== Montos =====
        // Suposición: valor_prestado está en Bs
        $importeRecibidoBs = (float) ($prestamo->valor_prestado ?? 0);      // Bs que recibes para cambiar
        $importeEntregadoUsd = $tc > 0 ? round($importeRecibidoBs / $tc, 2) : 0.0; // $us entregados
        $son = $this->montoUsdEnLetras($importeEntregadoUsd); // "CIENTO ... 94/100 DÓLARES"

        // Doc correlativo / referencia (sin request)
        $docSerie = 'PR'; // o lo que uses como serie
        $docNro = str_pad((string) $prestamo->id, 8, '0', STR_PAD_LEFT);
        $refPrestamo = $prestamo->numero ?? ('PR-'.$prestamo->id);

        $data = [
            'prestamo' => $prestamo,
            'agencia' => $agencia,
            'direccion' => $direccion,
            'usuario' => $usuario,
            'cuint' => $cuint,
            'cliente' => $cliente,
            'fechaAt' => $fechaAt,
            'montoBs' => $importeRecibidoBs,
            'montoUsd' => $importeEntregadoUsd,
            'son' => $son,
            'concepto' => 'Cambio Dólares (asociado a préstamo '.$refPrestamo.')',
            'docSerie' => $docSerie,
            'docNro' => $docNro,
            'refPrestamo' => $refPrestamo,
            'tipoCambio' => $tc,
        ];
        //        return $data;

        $pdf = Pdf::loadView('pdf.cambio', $data)->setPaper('letter', 'portrait');

        return $pdf->stream('comprobante-cambio-'.$refPrestamo.'.pdf');
    }

    /** "CIENTO CATORCE 94/100 DÓLARES" */
    private function montoUsdEnLetras(float $monto): string
    {
        $entero = (int) floor($monto + 0.00001);
        $cent = (int) round(($monto - $entero) * 100);
        $letras = $this->numeroEnLetrasEs($entero);
        $cent2 = str_pad((string) $cent, 2, '0', STR_PAD_LEFT);

        return mb_strtoupper(trim("$letras $cent2/100 Dólares"));
    }

    /** español simple 0–999,999,999 */
    private function numeroEnLetrasEs(int $n): string
    {
        if ($n === 0) {
            return 'CERO';
        }
        $u = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte'];
        $d = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $c = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        $t = function ($n) use ($u, $d) {
            if ($n <= 20) {
                return $u[$n];
            }
            $dec = intdiv($n, 10);
            $uni = $n % 10;
            if ($dec == 2 && $uni > 0) {
                return 'veinti'.$u[$uni];
            }

            return $d[$dec].($uni ? ' y '.$u[$uni] : '');
        };
        $h = function ($n) use ($c, $t) {
            if ($n == 100) {
                return 'cien';
            }
            $cen = intdiv($n, 100);
            $rest = $n % 100;

            return ($cen ? $c[$cen].($rest ? ' ' : '') : '').($rest ? $t($rest) : '');
        };

        $millones = intdiv($n, 1000000);
        $restoM = $n % 1000000;
        $miles = intdiv($restoM, 1000);
        $resto = $restoM % 1000;

        $parts = [];
        if ($millones) {
            $parts[] = ($millones == 1 ? 'un millón' : $h($millones).' millones');
        }
        if ($miles) {
            $parts[] = ($miles == 1 ? 'mil' : $h($miles).' mil');
        }
        if ($resto) {
            $parts[] = $h($resto);
        }
        $txt = implode(' ', $parts);
        // UNO -> UN cuando no es final (dejamos UNO si es número exacto 1)
        $txt = preg_replace('/\buno\b(?=\s+(mil|millones))/u', 'un', $txt);

        return mb_strtoupper($txt);
    }

    public function pdf(Prestamo $prestamo)
    {
        // Empresa (ajusta a tu fuente real)
        $empresa = [
            'nombre' => 'Joyería Rosario',
            'direccion' => 'Adolfomier entre potosi y pagador',
            'ciudad' => 'Oruro',
            'pais' => 'Bolivia',
            'cel' => '73800584',
        ];

        // Cálculos base (reusa tu helper interno)
        $base = $this->calcBase($prestamo);

        // Fechas
        $fc = $prestamo->fecha_creacion ? Carbon::parse($prestamo->fecha_creacion) : now();
        // Plazo fijo 30 días (ajusta si quieres variable)
        $plazoDias = 30;
        //        $vencimiento = $prestamo->fecha_limite
        //            ? Carbon::parse($prestamo->fecha_limite)
        //            : (clone $fc)->addDays($plazoDias);
        //        $vencimeinto es la fecha limite + un mes
        $vencimiento = $prestamo->fecha_limite
            ? Carbon::parse($prestamo->fecha_limite)->addMonthNoOverflow()
            : (clone $fc)->addMonthNoOverflow();

        // Moneda (elige tu etiqueta de visualización)
        // En tu imagen usas "SUS" (dólares). Puedes parametrizar si manejas múltiples monedas.
        $monedaCorta = 'SUS';
        $monedaLarga = 'Dólares';

        // Montos mostrados en el contrato
        $valorBienes = (float) ($prestamo->valor_total ?? 0);     // 8) Valor acordado de los bienes
        $capitalSolic = (float) ($prestamo->valor_prestado ?? 0);  // 9) Capital solicitado
        $interesMensual = (float) ($prestamo->interes ?? 0);         // % mensual
        $almacenMensual = (float) ($prestamo->almacen ?? 0);         // % mensual (conservación/almacén)
        $cargoMensual = round($capitalSolic * ($interesMensual + $almacenMensual) / 100, 2); // 12)

        // Detalle de joyas / pesos (usa nombres como en la imagen)
        $pesoTotalGr = (float) ($prestamo->peso ?? 0);            // 13) en gramos (si tus kg, conviértelo)
        $mermaGr = (float) ($prestamo->merma ?? 0);           // 14)
        $pesoOroGr = max(0, $pesoTotalGr - $mermaGr);           // 15)

        // Si en DB guardas kilos, descomenta esta conversión
        // $pesoTotalGr = round(($prestamo->peso ?? 0) * 1000, 3);
        // $mermaGr     = round(($prestamo->merma ?? 0) * 1000, 3);
        // $pesoOroGr   = max(0, round($pesoTotalGr - $mermaGr, 3));
        //        $dolar =6.96;
        $dolar = Cog::find(4)->value ?? 6.96;
        $data = [
            'empresa' => $empresa,

            // Cabecera
            'numero' => $prestamo->numero,
            'lugar' => $empresa['ciudad'] ?? 'Oruro',
            'fecha_creacion' => $fc,
            'plazoDias' => $plazoDias,
            'vencimiento' => $vencimiento,
            'monedaLarga' => $monedaLarga,
            'monedaCorta' => $monedaCorta,

            // Cliente
            'cliente' => $prestamo->cliente,    // ->name, ->ci si existe en tu modelo Client
            'cel' => $prestamo->celular,
            // Montos
            'valorBienes' => $valorBienes / $dolar,
            'capitalSolic' => $capitalSolic / $dolar,
            'interesMensual' => $interesMensual,
            'almacenMensual' => $almacenMensual,
            'cargoMensual' => $cargoMensual / $dolar,

            // Detalle / pesos
            'detalle' => $prestamo->detalle,
            'pesoTotalGr' => $pesoTotalGr,
            'mermaGr' => $mermaGr,
            'pesoOroGr' => $pesoOroGr,

            // Para mostrar como en la imagen (interés total del periodo de 30 días)
            'interesMonto30' => round($capitalSolic * $interesMensual / 100, 2),
            'almacenMonto30' => round($capitalSolic * $almacenMensual / 100, 2),
        ];

        $pdf = PDF::loadView('pdf.prestamo', $data)
            ->setPaper('letter', 'portrait'); // o A4

        $file = 'prestamo-'.($prestamo->numero ?: $prestamo->id).'.pdf';

        return $pdf->stream($file);
    }

    private function calcBase(Prestamo $p): array
    {
        $capital = (float) ($p->deuda_interes ?? 0);
        $tasaMensual = (float) ($p->interes ?? 0) + (float) ($p->almacen ?? 0); // %/mes
        $tasaDiaria = $tasaMensual / 100 / 30;

        $fechaBase = $p->fecha_creacion ? Carbon::parse($p->fecha_creacion) : today();
        $dias = max(0, $fechaBase->diffInDays(today()));

        $cargoDiario = round($capital * $tasaDiaria, 2);
        $cargos = round($cargoDiario * $dias, 2);

        $pagado = (float) $p->pagos()->where('estado', 'Activo')->sum('monto');
        $saldo = round($capital + $cargos - $pagado, 2);
        if ($saldo < 0) {
            $saldo = 0;
        }

        return compact('capital', 'tasaMensual', 'tasaDiaria', 'dias', 'cargoDiario', 'cargos', 'pagado', 'saldo');
    }

    private function crearPago(Prestamo $p, float $monto, string $tipo, string $metodo, int $userId): PrestamoPago
    {
        $pago = PrestamoPago::create([
            'fecha' => now()->toDateString(),
            'user_id' => $userId,
            'metodo' => $metodo ?: 'Efectivo',
            'tipo_pago' => $tipo,               // MENSUALIDAD | CARGOS | TOTAL
            'prestamo_id' => $p->id,
            'monto' => $monto,
            'estado' => 'Activo',
        ]);

        return $pago;
    }

    private function refreshSaldoEstado(Prestamo $p): void
    {
        $base = $this->calcBase($p);
        $p->saldo = $base['saldo'];
        if ($p->saldo <= 0) {
            $p->estado = 'Entregado';
        } elseif ($p->estado === 'Entregado') {
            $p->estado = 'Pendiente';
        }
        $p->save();
    }

    public function pagarMensualidad(Request $request, Prestamo $prestamo)
    {
        $user = $request->user();
        $metodo = (string) $request->input('metodo', 'Efectivo');
        $total_deuda = $prestamo->total_deuda;
        //        error_log('total_deuda: ' . $total_deuda);
        if ($total_deuda <= 0) {
            return response()->json(['message' => 'No hay deuda de interés por pagar'], 422);
        }
        $interes = $prestamo->interes;
        //        error_log('interes: ' . $interes);
        $almacen = $prestamo->almacen;
        //        error_log('almacen: ' . $almacen);

        $monto = round($total_deuda * ($interes + $almacen) / 100, 2);
        //        error_log('monto mensualidad: ' . $monto);

        $this->crearPago($prestamo, $monto, 'MENSUALIDAD', $metodo, $user->id);
        // Mover fecha límite +1 mes (si no existe, usa hoy)
        $nuevaLimite = ($prestamo->fecha_limite ? Carbon::parse($prestamo->fecha_limite) : today())->addMonthNoOverflow();
        $prestamo->fecha_limite = $nuevaLimite->toDateString();
        //        fecha_cancelacion aumetar un mes mas al dia de hoy now
        $hoy = now()->toDateString();
        //        $prestamo->fecha_cancelacion = date( "Y-m-d", strtotime( "$hoy +1 month" ) ); $prstamo fecha_milite agregar un mes
        $prestamo->fecha_cancelacion = date('Y-m-d', strtotime("$prestamo->fecha_cancelacion +1 month"));
        $prestamo->save();
        $this->refreshSaldoEstado($prestamo);

        //        return DB::transaction(function () use ($prestamo, $user, $metodo) {
        //            $base  = $this->calcBase($prestamo);
        //            $monto = min($base['saldo'], round($base['cargoDiario'] * 30, 2)); // paga solo 30 días
        //
        //            if ($monto <= 0) {
        //                return response()->json(['message' => 'No hay cargos por pagar'], 422);
        //            }
        //
        //            $this->crearPago($prestamo, $monto, 'MENSUALIDAD', $metodo, $user->id);
        //
        //            // Mover fecha límite +1 mes (si no existe, usa hoy)
        //            $nuevaLimite = ($prestamo->fecha_limite ? Carbon::parse($prestamo->fecha_limite) : today())->addMonthNoOverflow();
        //            $prestamo->fecha_limite = $nuevaLimite->toDateString();
        //            $prestamo->save();
        //
        //            $this->refreshSaldoEstado($prestamo);
        //
        //            return $prestamo->fresh()->load(['cliente','user']);
        //        });
    }

    public function pagarCargos(Request $request, Prestamo $prestamo)
    {
        $user = $request->user();
        $metodo = (string) $request->input('metodo', 'Efectivo');
        $monto = (float) $request->input('monto', 0);

        return DB::transaction(function () use ($prestamo, $user, $metodo, $monto) {
            //            $base = $this->calcBase($prestamo);

            // "Cargos pendientes" = (capital + cargos - pagado) - capital = cargos - pagado
            //            $cargosPend = max(0, round($base['cargos'] - $base['pagado'], 2));
            //            $monto      = min($cargosPend, $base['saldo']);

            if ($monto <= 0) {
                return response()->json(['message' => 'No hay cargos acumulados por pagar'], 422);
            }

            $this->crearPago($prestamo, $monto, 'CARGOS', $metodo, $user->id);

            // Ajusta fecha límite a HOY (como pediste)
            $prestamo->fecha_limite = today()->toDateString();
            $prestamo->fecha_cancelacion = date('Y-m-d', strtotime("$prestamo->fecha_limite +1 month"));
            $prestamo->save();

            $this->refreshSaldoEstado($prestamo);

            return $prestamo->fresh()->load(['cliente', 'user']);
        });
    }

    public function pagarTodo(Request $request, Prestamo $prestamo)
    {
        $user = $request->user();
        $metodo = (string) $request->input('metodo', 'Efectivo');

        // En tu front el toggle se llama "omitir_cargos"
        // Si omitir_cargos = true => paga SOLO capital
        // Si omitir_cargos = false => paga capital + cargos
        $omitirCargos = $request->boolean('omitir_cargos', false);

        return DB::transaction(function () use ($prestamo, $user, $metodo, $omitirCargos) {

            // 1) Capital pendiente (solo baja con pagos TOTAL o SALDO)
            $capitalPendiente = (float) $prestamo->total_deuda; // accessor tuyo

            // 2) Cargos/interés pendiente (calculado dinámico)
            $cargosPendiente = (float) $prestamo->deuda_interes; // accessor tuyo

            if ($capitalPendiente <= 0 && $cargosPendiente <= 0) {
                // ya está pagado todo
                $prestamo->estado = 'Entregado';
                $prestamo->fecha_limite = today()->toDateString();
                //                fecha_cancelacion es un mes mas la fecha limite
                $prestamo->fecha_cancelacion = date('Y-m-d', strtotime("$prestamo->fecha_limite +1 month"));
                $prestamo->save();

                return response()->json(['message' => 'El préstamo ya está pagado'], 200);
            }

            // =========================
            // A) Si NO omite cargos => crea 2 pagos: CARGOS + TOTAL
            // =========================
            if (! $omitirCargos) {

                // PAGO 1: cargos (interés)
                if ($cargosPendiente > 0) {
                    $this->crearPago($prestamo, $cargosPendiente, 'CARGOS', $metodo, $user->id);
                }

                // PAGO 2: capital
                if ($capitalPendiente > 0) {
                    $this->crearPago($prestamo, $capitalPendiente, 'TOTAL', $metodo, $user->id);
                }

                // al pagar cargos, cortas el conteo desde hoy
                $prestamo->fecha_limite = today()->toDateString();
                $prestamo->save();
            }

            // =========================
            // B) Si OMITE cargos => paga SOLO capital (1 pago)
            // =========================
            if ($omitirCargos) {
                if ($capitalPendiente > 0) {
                    $this->crearPago($prestamo, $capitalPendiente, 'TOTAL', $metodo, $user->id);
                }

                // OJO: aquí NO estás pagando cargos, así que lo normal es
                // NO mover fecha_limite a hoy (si la mueves, “perdonas” el interés)
                // Déjala como está.
            }

            // 3) Re-evaluar estado con tus accessors
            $prestamo->refresh(); // recarga y recalcula accessors
            $cap = (float) $prestamo->total_deuda;
            $car = (float) $prestamo->deuda_interes;

            if ($cap <= 0 && $car <= 0) {
                $prestamo->estado = 'Entregado';
            } else {
                // si queda algo (por ejemplo omitiste cargos), sigue activo
                $prestamo->estado = 'Activo';
            }

            $prestamo->save();

            return $prestamo->fresh()->load(['cliente', 'user']);
        });
    }

    protected function retrasadosQuery(Request $request)
    {
        $hoy = now()->toDateString();
        $diasMin = max(1, (int) $request->query('dias', 1));
        $userId = $request->query('user_id');
        $search = trim((string) $request->query('search', ''));
        $connection = DB::connection()->getDriverName();
        $diffExpr = $connection === 'sqlite'
            ? 'CAST(julianday(?) - julianday(fecha_limite) AS INTEGER)'
            : 'DATEDIFF(?, fecha_limite)';

        $q = Prestamo::with(['cliente', 'user'])
            ->whereNotIn('estado', ['Entregado', 'Cancelado'])
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', $hoy)
            ->whereRaw("{$diffExpr} >= ?", [$hoy, $diasMin])
            ->select('*')
            ->selectRaw("{$diffExpr} as dias_retraso", [$hoy])
            ->orderBy('dias_retraso')
            ->orderBy('fecha_limite');

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

        return $q;
    }

    public function retrasados(Request $request)
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 24)));
        $q = $this->retrasadosQuery($request);

        $items = $q->paginate($perPage)->appends($request->query());
        $collection = $q->get();

        $summary = [
            'total' => $collection->count(),
            'saldo' => round((float) $collection->sum(fn ($prestamo) => (float) ($prestamo->saldo ?? 0)), 2),
            'capital_invertido' => round((float) $collection->sum(fn ($prestamo) => (float) ($prestamo->valor_prestado ?? 0)), 2),
            'prom_dias' => round((float) $collection->avg('dias_retraso'), 1),
            'max_dias' => (int) $collection->max('dias_retraso'),
        ];

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
            'summary' => $summary,
        ]);
    }

    public function retrasadosExport(Request $request): StreamedResponse
    {
        $rows = $this->retrasadosQuery($request)->get();
        $fileName = 'prestamos-retrasados-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, '<html><head><meta charset="UTF-8"></head><body>');
            fwrite($handle, '<table border="1">');
            fwrite($handle, '<thead><tr>');

            foreach ([
                'Numero',
                'Cliente',
                'CI',
                'Celular',
                'Fecha limite',
                'Dias retraso',
                'Peso',
                'Precio oro',
                'Capital prestado',
                'Saldo actual',
                'Estado',
                'Usuario',
                'Detalle',
            ] as $header) {
                fwrite($handle, '<th>'.$this->escapeExcelCell($header).'</th>');
            }

            fwrite($handle, '</tr></thead><tbody>');

            foreach ($rows as $prestamo) {
                fwrite($handle, '<tr>');

                foreach ([
                    $prestamo->numero,
                    $prestamo->cliente?->name,
                    $prestamo->cliente?->ci,
                    $prestamo->celular,
                    $prestamo->fecha_limite,
                    $prestamo->dias_retraso,
                    number_format((float) ($prestamo->peso ?? 0), 2, '.', ''),
                    number_format((float) ($prestamo->precio_oro ?? 0), 2, '.', ''),
                    number_format((float) ($prestamo->valor_prestado ?? 0), 2, '.', ''),
                    number_format((float) ($prestamo->saldo ?? 0), 2, '.', ''),
                    $prestamo->estado,
                    $prestamo->user?->name,
                    $prestamo->detalle,
                ] as $value) {
                    fwrite($handle, '<td>'.$this->escapeExcelCell((string) $value).'</td>');
                }

                fwrite($handle, '</tr>');
            }

            fwrite($handle, '</tbody></table></body></html>');

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function escapeExcelCell(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $estado = $request->query('estado', 'Todos');
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 12);

        $q = Prestamo::with(['cliente', 'user'])->orderBy('id', 'desc');
        if ($userId) {
            $q->where('user_id', $userId);
        }
        if ($request->filled('cliente_id')) {
            $q->where('cliente_id', (int) $request->query('cliente_id'));
        }
        if ($estado && $estado !== 'Todos') {
            $q->where('estado', $estado);
        }

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
        return $prestamo->load(['cliente', 'user']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'required|integer',
            'user_id' => 'required|integer|exists:users,id',
            'fecha_creacion' => 'nullable|date',
            'fecha_limite' => 'nullable|date',
            'peso' => 'required|numeric|min:0',  // kg (bruto)
            'merma' => 'required|numeric|min:0',  // kg
            'valor_prestado' => 'required|numeric|min:0.01',
            'interes' => 'required|numeric|gte:1|lte:3',  // %
            'almacen' => 'required|numeric|gte:1|lte:3',  // %
            'celular' => 'nullable|string',
            'detalle' => 'nullable|string',
        ]);

        $isAdmin = ($request->user()?->role ?? null) === 'Administrador';

        return DB::transaction(function () use ($data, $isAdmin) {
            $precioOro = $this->precioPrestamoActual();

            // Peso neto con merma
            $pesoBruto = (float) $data['peso'];
            $merma = (float) $data['merma'];
            $pesoNeto = max(0, $pesoBruto - $merma);

            // Valor referencial por peso NETO
            $valorTotal = round($pesoNeto * (float) $precioOro, 2);

            // Deuda teórica según % (sobre lo prestado)
            $vp = (float) $data['valor_prestado'];
            $i = (float) $data['interes']; // %
            $a = $this->resolveAlmacenRate($vp, $isAdmin, $data['almacen'] ?? null);
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);

            $tipoDeCambio = Cog::find(4);

            $prestamo = Prestamo::create([
                'numero' => null,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'fecha_limite' => date('Y-m-d'),
                'fecha_cancelacion' => $data['fecha_limite'] ?? null,
                'cliente_id' => $data['cliente_id'],
                'user_id' => $data['user_id'],
                'peso' => $pesoBruto,
                'merma' => $merma,
                'peso_neto' => $pesoNeto,    // opcional si tienes la columna
                'precio_oro' => $precioOro,
                'valor_total' => $valorTotal,
                'valor_prestado' => $vp,
                'interes' => $i,          // %
                'almacen' => $a,          // %
                'saldo' => $deuda,      // total teórico
                'celular' => $data['celular'] ?? null,
                'detalle' => ($data['detalle'] ?? null).(' [TC: '.($tipoDeCambio ? $tipoDeCambio->value : 'N/A').']'),
                'estado' => 'Activo',
            ]);

            $prestamo->numero = 'PR-'.str_pad($prestamo->id, 6, '0', STR_PAD_LEFT).'-'.now()->format('Y');
            $prestamo->save();

            return $prestamo->load(['cliente', 'user']);
        });
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        $isAdmin = ($request->user()?->role ?? null) === 'Administrador';

        if (! $isAdmin) {
            return response()->json(['message' => 'No autorizado para editar este prestamo'], 403);
        }

        $data = $request->validate([
            'fecha_creacion' => 'nullable|date',
            'fecha_limite' => 'nullable|date',
            'interes' => 'nullable|numeric|gte:1|lte:3',
            'almacen' => 'nullable|numeric|gte:1|lte:3',
        ]);

        return DB::transaction(function () use ($prestamo, $data) {
            $vp = (float) $prestamo->valor_prestado;
            $i = array_key_exists('interes', $data) ? (float) $data['interes'] : (float) $prestamo->interes;
            $a = $this->resolveAlmacenRate($vp, true, $data['almacen'] ?? $prestamo->almacen);
            $pagado = $prestamo->pagos()->where('estado', 'Activo')->sum('monto');
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);
            $saldo = $deuda - $pagado;

            $prestamo->update([
                'fecha_creacion' => $data['fecha_creacion'] ?? $prestamo->fecha_creacion,
                'fecha_limite' => $data['fecha_limite'] ?? $prestamo->fecha_limite,
                'interes' => $i,
                'almacen' => $a,
                'saldo' => $saldo,
                'estado' => $saldo <= 0 ? 'Entregado' : $prestamo->estado,
            ]);

            return $prestamo->load(['cliente', 'user']);
        });
    }

    private function resolveAlmacenRate(float $valorPrestado, bool $isAdmin, mixed $requestedRate): float
    {
        if (! $isAdmin && $valorPrestado > 1000) {
            return 2.0;
        }

        return (float) $requestedRate;
    }

    /* ======= PAGOS ======= */

    public function pagos(Prestamo $prestamo)
    {
        return $prestamo->pagos()->with('user')->orderBy('id', 'desc')->get();
    }

    public function pagar(Request $request)
    {
        $data = $request->validate([
            'prestamo_id' => 'required|integer|exists:prestamos,id',
            'fecha' => 'nullable|date',
            'monto' => 'required|numeric|min:0.01',
        ]);
        //        verificar que no tenga interes
        $prestamos = Prestamo::find($data['prestamo_id']);
        if ($prestamos->deuda_interes > 0) {
            return response()->json(['message' => 'El préstamo tiene intereses pendientes, no se puede registrar el pago.'], 422);
        }
        $user = $request->user();

        return DB::transaction(function () use ($data, $user, $request) {
            $prestamo = Prestamo::findOrFail($data['prestamo_id']);

            PrestamoPago::create([
                'fecha' => now()->toDateString(), // o $data['fecha']
                'user_id' => $user->id,
                'metodo' => $request->input('metodo', 'Efectivo'),
                'tipo_pago' => $request->input('tipo_pago', 'SALDO'), // Abono | Pago Total
                'prestamo_id' => $prestamo->id,
                'monto' => $data['monto'],
                'estado' => 'Activo',
            ]);
            $tipo_pago = $request->input('tipo_pago', 'SALDO');
            if ($tipo_pago == 'ADICIONAR CAPITAL') {
                $prestamo->valor_prestado = $prestamo->valor_prestado + $data['monto'];
                $prestamo->save();
            }

            return $prestamo->pagos()->with('user')->latest()->first();
        });
    }

    public function anularPago(PrestamoPago $pago)
    {
        return DB::transaction(function () use ($pago) {
            $pago->estado = 'Anulado';
            $pago->save();

            $prestamo = $pago->prestamo()->first();
            if (($pago->tipo_pago ?? null) === 'ADICIONAR CAPITAL') {
                $prestamo->valor_prestado = max(0, (float) $prestamo->valor_prestado - (float) $pago->monto);
            }
            $pagado = $prestamo->pagos()->where('estado', 'Activo')->sum('monto');

            $vp = (float) $prestamo->valor_prestado;
            $i = (float) $prestamo->interes;  // %
            $a = (float) $prestamo->almacen;  // %
            $deuda = round($vp + ($vp * $i / 100) + ($vp * $a / 100), 2);

            $prestamo->saldo = $deuda - $pagado;
            if ($prestamo->saldo > 0 && $prestamo->estado === 'Entregado') {
                $prestamo->estado = 'Pendiente';
            }
            $prestamo->save();

            return response()->json(['message' => 'Pago anulado']);
        });
    }

    public function toggleMetodoPago(Request $request, PrestamoPago $pago)
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
}
