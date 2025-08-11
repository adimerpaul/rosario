<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller{
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
