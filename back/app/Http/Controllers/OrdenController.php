<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Illuminate\Http\Request;

class OrdenController extends Controller{
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
        error_log($ultimoNumero);
        if ($ultimoNumero) {
            $numero = $ultimoNumero->numero;
            error_log('Ultimo numero: ' . $numero);
            $numeroPartes = explode('-', $numero);
            error_log(json_encode($numeroPartes));
            $numeroPartesultimosDigitos = substr($numeroPartes[0], 1, 4);
            $numeroSecuencia = (int)$numeroPartesultimosDigitos + 1;
            error_log('Numero secuencia: ' . $numeroSecuencia);
//            $numeroSecuencia = (int)substr($numeroPartes[0],1, 5) + 1;
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
}
