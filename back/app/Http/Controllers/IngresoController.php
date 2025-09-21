<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use Illuminate\Http\Request;

class IngresoController extends Controller
{
    // Lista por fecha (para Libro Diario)
    public function index(Request $request)
    {
        $date = $request->query('date'); // YYYY-MM-DD
        $q = Ingreso::with(['user:id,name'])
            ->when($date, fn($qq) => $qq->whereDate('fecha', $date))
            ->orderBy('created_at', 'asc');

        return response()->json($q->get());
    }

    // Crear ingreso
    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'       => 'required|date',
            'descripcion' => 'required|string|max:255',
            'metodo'      => 'required|in:EFECTIVO,QR',
            'monto'       => 'required|numeric|min:0.01',
            'nota'        => 'nullable|string|max:255',
        ]);

        $data['user_id'] = optional($request->user())->id;

        $ingreso = Ingreso::create($data);
        return response()->json($ingreso->load('user:id,name'), 201);
    }

    // Anular (solo admin)
    public function anular(Request $request, Ingreso $ingreso)
    {
        $user = $request->user();
        if (!$user || ($user->role ?? null) !== 'Administrador') {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($ingreso->estado === 'Anulado') {
            return response()->json(['message' => 'El ingreso ya está anulado'], 422);
        }

        $ingreso->update([
            'estado'      => 'Anulado',
            'anulado_por' => $user->id,
            'anulado_at'  => now(),
        ]);

        return response()->json($ingreso->fresh()->load('user:id,name'));
    }
}
