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
            'numero' => 'required|unique:ordenes',
            'fecha_creacion' => 'required|date',
            'costo_total' => 'required|numeric',
            'adelanto' => 'required|numeric',
            'saldo' => 'required|numeric',
            'user_id' => 'required|exists:users,id',
        ]);

        return Orden::create($request->all());
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
