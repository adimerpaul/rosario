<?php

namespace App\Http\Controllers;

use App\Models\VitrinaColumna;
use Illuminate\Http\Request;

class VitrinaColumnaController extends Controller
{
    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'vitrina_id' => 'required|exists:vitrinas,id',
            'codigo' => 'required|string|max:255|unique:vitrina_columnas,codigo',
            'orden' => 'nullable|integer|min:1',
        ]);

        if (! isset($data['orden'])) {
            $data['orden'] = (int) VitrinaColumna::where('vitrina_id', $data['vitrina_id'])->max('orden') + 1;
        }

        return VitrinaColumna::create($data);
    }

    public function update(Request $request, VitrinaColumna $columna)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'codigo' => 'required|string|max:255|unique:vitrina_columnas,codigo,'.$columna->id,
            'orden' => 'required|integer|min:1',
        ]);

        $columna->update($data);

        return $columna->fresh();
    }

    public function destroy(Request $request, VitrinaColumna $columna)
    {
        $this->ensureAdmin($request);

        if ($columna->estuches()->exists()) {
            return response()->json(['message' => 'No se puede eliminar una columna con estuches'], 422);
        }

        $columna->delete();

        return response()->json(['message' => 'Columna eliminada']);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'Administrador', 403, 'No autorizado');
    }
}
