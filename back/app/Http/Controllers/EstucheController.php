<?php

namespace App\Http\Controllers;

use App\Models\Estuche;
use Illuminate\Http\Request;

class EstucheController extends Controller
{
    public function options(Request $request)
    {
        $this->ensureViewer($request);

        return Estuche::with(['columna.vitrina', 'joya:id,nombre,estuche_id'])
            ->orderBy('nombre')
            ->get()
            ->map(function (Estuche $estuche) {
                return [
                    'id' => $estuche->id,
                    'nombre' => $estuche->nombre,
                    'label' => $estuche->columna?->vitrina?->nombre.' / '.$estuche->columna?->codigo.' / '.$estuche->nombre,
                    'ocupado' => $estuche->joya !== null,
                    'joya_id' => $estuche->joya?->id,
                ];
            })->values();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'vitrina_columna_id' => 'required|exists:vitrina_columnas,id|unique:estuches,vitrina_columna_id',
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

        if ($estuche->joya) {
            $estuche->joya->update(['estuche' => $estuche->nombre]);
        }

        return $estuche->fresh();
    }

    public function destroy(Request $request, Estuche $estuche)
    {
        $this->ensureAdmin($request);

        if ($estuche->joya) {
            return response()->json(['message' => 'No se puede eliminar un estuche con joya asignada'], 422);
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
