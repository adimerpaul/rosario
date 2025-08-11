<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller{
    public function garantia(Orden $orden)
    {
        $orden->load(['cliente','user']);

        // Datos de cabecera (ajusta a tu realidad)
        $empresa = [
            'nombre'   => 'JOYERIA ROSARIO',
            'sucursal' => 'ORURO',
            'direccion'=> 'Calle Junín esq. La Plata, frente al mercado',
            'cel'      => '704-12345',
        ];

        // Config de garantía (personaliza si quieres leer de BD/config)
        $garantia = [
            'codigo'                => $orden->numero,
            'fecha'                 => now(), // o $orden->fecha_entrega
            'cliente'               => $orden->cliente->name ?? 'N/A',
            'tipo'                  => 'Joya',
            'periodo'               => '1 año',
            'detalle'               => $orden->detalle,
            'mantenimiento_meses'   => 12, // o 6
        ];

        // (Opcional) precio del oro si lo quieres mostrar en cabecera
        $precioOro = 0;
        if (DB::getSchemaBuilder()->hasTable('cogs')) {
            $cog = DB::table('cogs')->where('id', 2)->first();
            $precioOro = $cog ? ($cog->value ?? $cog->valor ?? 0) : 0;
        }

        $pdf = Pdf::loadView('pdf.garantia', [
            'empresa'   => $empresa,
            'garantia'  => $garantia,
            'precioOro' => $precioOro,
            'hoy'       => now(),
        ])->setPaper('A4', 'portrait');

        return $pdf->stream('garantia_'.$orden->numero.'.pdf');
        // return $pdf->download('garantia_'.$orden->numero.'.pdf');
    }
    function cancelar(Request $request,Orden $orden){
        // opcional: motivo de cancelación
//        $request->validate([
//            'motivo' => 'nullable|string|max:255',
////            'anular_pagos' => 'sometimes|boolean',
//        ]);

        if ($orden->estado === 'Cancelada') {
            return response()->json(['message' => 'La orden ya está cancelada'], 409);
        }
        if ($orden->estado === 'Entregado') {
            return response()->json(['message' => 'No se puede cancelar una orden entregada'], 422);
        }

        DB::transaction(function () use ($orden, $request) {
            // marcar cancelada
            $orden->estado = 'Cancelada';

            // si quieres que saldo quede en 0 al cancelar, descomenta:
            // $orden->saldo = 0;

            // si quieres anular pagos activos al cancelar:
            if ($request->boolean('anular_pagos')) {
                $orden->pagos()->where('estado', 'Activo')->update(['estado' => 'Anulado']);
                // y revertir adelanto/saldo:
                $montoAnulado = $orden->pagos()->where('estado','Anulado')->sum('monto');
                // recomputa adelanto con solo pagos activos
                $nuevoAdelanto = $orden->pagos()->where('estado','Activo')->sum('monto');
                $orden->adelanto = $nuevoAdelanto;
                $orden->saldo = max(0, ($orden->costo_total ?? 0) - $nuevoAdelanto);
            }

            $orden->save();
        });

        return response()->json($orden->fresh(['cliente','user']));
    }
    public function show(Orden $orden){
        return Orden::with(['cliente:id,name,ci,status,cellphone'])->findOrFail($orden->id);
    }
    public function index(Request $request)
    {
        $query = Orden::with(['user:id,name', 'cliente:id,name'])
            ->orderBy('fecha_creacion', 'desc');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_creacion', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->has('user_id') && $request->user_id !== null && $request->user_id !== 'null') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }

        return $query->paginate(100);
    }

    public function store(Request $request)
    {
        $request->validate([
//            'numero' => 'required|unique:ordenes',
//            'fecha_creacion' => 'required|date',
            'costo_total' => 'required|numeric',
            'adelanto' => 'required|numeric',
            'saldo' => 'required|numeric',
//            'user_id' => 'required|exists:users,id',
        ]);
        $numero =$this->numeroGet();
        error_log($numero);
        $user = $request->user();
        $request->merge([
            'numero' => $numero,
            'fecha_creacion' => now(),
            'user_id' => $user->id,
        ]);

        return Orden::create($request->all());
    }
    function numeroGet(){
        $year = date('Y');
        $ultimoNumero = Orden::orderBy('id', 'desc')
            ->where('numero', 'like', 'O%'. '-'.$year)
            ->first();
        if ($ultimoNumero) {
            $numero = $ultimoNumero->numero;
            $numeroPartes = explode('-', $numero);
            $numeroPartesultimosDigitos = substr($numeroPartes[0], 1, 4);
            $numeroSecuencia = (int)$numeroPartesultimosDigitos + 1;
            $anio = date('Y');
            return 'O'.str_pad($numeroSecuencia, 4, '0', STR_PAD_LEFT) . '-' . $anio;
        } else {
            return 'O0001-' . date('Y');
        }
    }

    public function update(Request $request, Orden $orden)
    {
        $orden->update($request->all());
        return $orden;
    }

    public function destroy(Orden $orden)
    {
        $orden->delete();
        return response()->json(['message' => 'Orden eliminada']);
    }

    public function pdf(Orden $orden)
    {
        // Cargar relaciones
        $orden->load(['cliente', 'user']);

        // Traer precio del oro (usa tu fuente real; aquí ejemplo desde cogs id=2)
        $precioOro = 0;
        try {
            $cog = DB::table('cogs')->where('id', 2)->first();
            $precioOro = $cog ? ($cog->value ?? $cog->valor ?? 0) : 0;
        } catch (\Throwable $e) {
            $precioOro = 0;
        }

        // Datos para el encabezado
        $empresa = [
            'nombre' => 'Joyeria Rosario',
            'sucursal' => 'ORURO',
            'direccion' => 'Calle Junín entre La Plata y Soria — Frente a mercado',
            'cel' => '704-12345',
            'nit' => '12345601',
        ];

        // Render del PDF
        $pdf = Pdf::loadView('pdf.orden_trabajo', [
            'orden' => $orden,
            'empresa' => $empresa,
            'precioOro' => $precioOro,
            'hoy' => now(),
        ])->setPaper('A4', 'portrait');

        // Mostrar en el navegador
        $fileName = 'orden_trabajo_'.$orden->numero.'.pdf';
        return $pdf->stream($fileName);
        // Si prefieres descarga:
        // return $pdf->download($fileName);
    }
}
