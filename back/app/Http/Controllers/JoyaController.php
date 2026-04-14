<?php

namespace App\Http\Controllers;

use App\Models\Estuche;
use App\Models\Joya;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class JoyaController extends Controller
{
    public function index(Request $request)
    {
        //        $this->ensureAdmin($request);

        $search = trim((string) $request->input('search', ''));
        $soloSinEstuche = $request->boolean('sin_estuche', false);
        $perPage = (int) $request->input('per_page', 12);

        $query = Joya::query()
            ->with([
                'estucheItem.columna.vitrina',
                'user:id,name,username',
                'ventas' => function ($query) {
                    $query->where('tipo', 'Venta directa')->orderByDesc('id');
                },
                'ventasItems' => function ($query) {
                    $query->where('tipo', 'Venta directa')->orderByDesc('id');
                },
            ])
            ->when($soloSinEstuche, fn ($query) => $query->whereNull('estuche_id')->where('vendido', false))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhere('linea', 'like', "%{$search}%")
                        ->orWhere('estuche', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($soloSinEstuche) {
            return $query->get()->map(fn (Joya $joya) => $this->appendEstadoJoya($joya));
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage)->appends($request->query());
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Joya $joya) => $this->appendEstadoJoya($joya))
        );

        return $paginator;
    }

    public function store(Request $request)
    {
        $this->ensureCreateAccess($request);

        $data = $this->validateData($request);
        $data['user_id'] = $request->user()->id;
        $data['created_at'] = now();

        return Joya::create($this->normalizeData($data));
    }

    public function update(Request $request, Joya $joya)
    {
        $this->ensureAdmin($request);

        $data = $this->validateData($request);
        if (! $joya->created_at) {
            $data['created_at'] = now();
        }

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

    public function asignarEstuche(Request $request, Joya $joya)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'estuche_id' => 'required|exists:estuches,id',
        ]);

        $estuche = Estuche::findOrFail($data['estuche_id']);

        $joya->update([
            'estuche_id' => $estuche->id,
            'estuche' => $estuche->nombre,
        ]);

        return $joya->fresh()->load('estucheItem.columna.vitrina');
    }

    public function quitarEstuche(Request $request, Joya $joya)
    {
        $this->ensureAdmin($request);

        $joya->update([
            'estuche_id' => null,
            'estuche' => null,
        ]);

        return response()->json(['message' => 'Joya retirada del estuche']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'tipo' => 'required|string|in:Importada,Joya nacional,Plata',
            'peso' => 'required|numeric|min:0',
            'linea' => 'required|string|in:Mama,Papa,Roger,Andreina',
            'estuche_id' => ['nullable', 'exists:estuches,id'],
            'nombre' => 'required|string|max:255',
            'monto_bs' => 'required|numeric|min:0',
            'vendido' => 'nullable|boolean',
        ]);
    }

    private function normalizeData(array $data): array
    {
        if (isset($data['nombre']) && is_string($data['nombre'])) {
            $data['nombre'] = mb_strtoupper(trim($data['nombre']), 'UTF-8');
        }

        if (array_key_exists('estuche_id', $data) && $data['estuche_id']) {
            $estuche = Estuche::find($data['estuche_id']);
            $data['estuche'] = $estuche?->nombre;
        } elseif (array_key_exists('estuche_id', $data)) {
            $data['estuche'] = null;
        }

        return $data;
    }

    private function ensureAdmin(Request $request): void
    {
        $role = $request->user()?->role;

        abort_unless($role === 'Administrador', 403, 'No autorizado');
    }

    private function ensureCreateAccess(Request $request): void
    {
        $role = $request->user()?->role;

        abort_unless(in_array($role, ['Administrador', 'Vendedor'], true), 403, 'No autorizado');
    }

    private function appendEstadoJoya(Joya $joya): Joya
    {
        $ultimaVenta = $this->ultimaVentaDirecta($joya);

        $joya->setAttribute('estado_joya', $this->estadoJoya($ultimaVenta));
        $joya->setAttribute('venta_id', $ultimaVenta?->id);
        $joya->setAttribute('numero_venta', $ultimaVenta?->numero);

        return $joya;
    }

    private function ultimaVentaDirecta(Joya $joya): ?Orden
    {
        return collect()
            ->merge($joya->ventas ?? collect())
            ->merge($joya->ventasItems ?? collect())
            ->unique('id')
            ->sortByDesc('id')
            ->first();
    }

    private function estadoJoya(?Orden $venta): string
    {
        if (! $venta) {
            return 'DISPONIBLE';
        }

        if ($venta->estado === 'Cancelada') {
            return 'ANULADO';
        }

        if ($venta->estado === 'Entregado') {
            return 'VENDIDO';
        }

        return 'RESERVADO';
    }
}
