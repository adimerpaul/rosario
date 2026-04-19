<?php

namespace App\Http\Controllers;

use App\Models\Vitrina;
use Illuminate\Http\Request;

class VitrinaController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureViewer($request);

        return Vitrina::with([
            'columnas.estuches.joyas' => fn ($query) => $query
                ->with([
                    'ventas' => fn ($ventaQuery) => $ventaQuery
                        ->where('tipo', 'Venta directa')
                        ->where('estado', '!=', 'Cancelada')
                        ->orderByDesc('id'),
                    'ventasItems' => fn ($ventaQuery) => $ventaQuery
                        ->where('tipo', 'Venta directa')
                        ->where('estado', '!=', 'Cancelada')
                        ->orderByDesc('id'),
                ]),
        ])->orderBy('orden')->get();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:vitrinas,nombre',
            'orden' => 'nullable|integer|min:1',
        ]);

        if (! isset($data['orden'])) {
            $data['orden'] = (int) Vitrina::max('orden') + 1;
        }

        return Vitrina::create($data);
    }

    public function update(Request $request, Vitrina $vitrina)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:vitrinas,nombre,'.$vitrina->id,
            'orden' => 'required|integer|min:1',
        ]);

        $vitrina->update($data);

        return $vitrina->fresh();
    }

    public function destroy(Request $request, Vitrina $vitrina)
    {
        $this->ensureAdmin($request);

        if ($vitrina->columnas()->exists()) {
            return response()->json(['message' => 'No se puede eliminar una vitrina con columnas'], 422);
        }

        $vitrina->delete();

        return response()->json(['message' => 'Vitrina eliminada']);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'Administrador', 403, 'No autorizado');
    }

    private function ensureViewer(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['Administrador', 'Vendedor'], true), 403, 'No autorizado');
    }
}
