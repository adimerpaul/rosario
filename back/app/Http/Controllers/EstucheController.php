<?php

namespace App\Http\Controllers;

use App\Models\Estuche;
use Illuminate\Http\Request;

class EstucheController extends Controller
{
    public function options(Request $request)
    {
        $this->ensureViewer($request);

        return Estuche::with(['columna.vitrina', 'joyas' => fn ($query) => $query->where('vendido', false)->select('id', 'nombre', 'estuche_id')])
            ->orderBy('nombre')
            ->get()
            ->map(function (Estuche $estuche) {
                return [
                    'id' => $estuche->id,
                    'nombre' => $estuche->nombre,
                    'label' => $estuche->columna?->vitrina?->nombre.' / '.$estuche->columna?->codigo.' / '.$estuche->nombre,
                    'ocupado' => $estuche->joyas->isNotEmpty(),
                    'joyas_count' => $estuche->joyas->count(),
                ];
            })->values();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'vitrina_columna_id' => 'required|exists:vitrina_columnas,id',
            'nombre' => 'required|string|max:255',
            'orden' => 'nullable|integer|min:1',
        ]);

        if (! isset($data['orden'])) {
            $data['orden'] = 1;
        }

        return Estuche::create($this->normalizeData($data));
    }

    public function update(Request $request, Estuche $estuche)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'orden' => 'required|integer|min:1',
        ]);

        $estuche->update($this->normalizeData($data));

        if ($estuche->joyas()->exists()) {
            $estuche->joyas()->update(['estuche' => $estuche->nombre]);
        }

        return $estuche->fresh();
    }

    public function destroy(Request $request, Estuche $estuche)
    {
        $this->ensureAdmin($request);

        if ($estuche->joyas()->exists()) {
            return response()->json(['message' => 'No se puede eliminar un estuche con joyas asignadas'], 422);
        }

        $estuche->delete();

        return response()->json(['message' => 'Estuche eliminado']);
    }

    private function normalizeData(array $data): array
    {
        $data['nombre'] = mb_strtoupper(trim($data['nombre']), 'UTF-8');

        return $data;
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
