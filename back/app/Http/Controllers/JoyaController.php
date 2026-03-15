<?php

namespace App\Http\Controllers;

use App\Models\Joya;
use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class JoyaController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $search = trim((string) $request->input('search', ''));

        return Joya::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhere('linea', 'like', "%{$search}%")
                        ->orWhere('estuche', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $this->validateData($request);

        return Joya::create($this->normalizeData($data));
    }

    public function update(Request $request, Joya $joya)
    {
        $this->ensureAdmin($request);

        $data = $this->validateData($request);

        $joya->update($this->normalizeData($data));

        return $joya->fresh();
    }

    public function destroy(Request $request, Joya $joya)
    {
        $this->ensureAdmin($request);

        $joya->delete();

        return response()->json(['message' => 'Joya eliminada']);
    }

    public function updateImagen(Request $request, Joya $joya)
    {
        $this->ensureAdmin($request);

        $request->validate([
            'imagen' => 'required|image|max:5120',
        ]);

        $file = $request->file('imagen');
        $filename = time().'_'.$joya->id.'.jpg';
        $path = public_path('images/'.$filename);

        $manager = new ImageManager(new Driver);

        $manager->read($file->getPathname())
            ->cover(600, 600)
            ->toJpeg(75)
            ->save($path);

        $joya->update(['imagen' => $filename]);

        return response()->json([
            'message' => 'Imagen actualizada',
            'imagen' => $filename,
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'tipo' => 'required|string|in:Importada,Joya nacional,Plata',
            'peso' => 'required|numeric|min:0',
            'linea' => 'required|string|in:Mama,Papa,Roger,Andreina',
            'estuche' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'monto_bs' => 'required|numeric|min:0',
        ]);
    }

    private function normalizeData(array $data): array
    {
        foreach (['estuche', 'nombre'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = mb_strtoupper(trim($data[$field]), 'UTF-8');
            }
        }

        return $data;
    }

    private function ensureAdmin(Request $request): void
    {
        $role = $request->user()?->role;

        abort_unless($role === 'Administrador', 403, 'No autorizado');
    }
}
