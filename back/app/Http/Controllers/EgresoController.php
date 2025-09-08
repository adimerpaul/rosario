<?php

namespace App\Http\Controllers;

use App\Models\Egreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EgresoController extends Controller
{
    // Lista por fecha (para el Libro Diario)
    public function index(Request $request)
    {
        $date = $request->query('date'); // YYYY-MM-DD
        $q = Egreso::with(['user:id,name'])
            ->when($date, fn($qq) => $qq->whereDate('fecha', $date))
            ->orderBy('created_at', 'asc');

        return response()->json($q->get());
    }

    // Crear egreso
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

        $egreso = Egreso::create($data);
        return response()->json($egreso->load('user:id,name'), 201);
    }

    // Anular (solo admin)
    public function anular(Request $request, Egreso $egreso)
    {
        $user = $request->user();
        if (!$user || ($user->role ?? null) !== 'Admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($egreso->estado === 'Anulado') {
            return response()->json(['message' => 'El egreso ya está anulado'], 422);
        }

        $egreso->update([
            'estado'      => 'Anulado',
            'anulado_por' => $user->id,
            'anulado_at'  => now(),
        ]);

        return response()->json($egreso->fresh()->load('user:id,name'));
    }
}
