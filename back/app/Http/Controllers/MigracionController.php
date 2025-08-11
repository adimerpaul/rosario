<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientOld;
use Illuminate\Http\Request;

class MigracionController extends Controller{
    public function migrate()
    {
        $oldClients = ClientOld::all();
        $migrated = 0;
        $failed = 0;

        foreach ($oldClients as $old) {
            try {
                Client::create([
                    'name'        => $old->first_name . ' ' . $old->last_name,
                    'ci'          => $old->ci,
                    'carnet'      => null, // Puedes modificar si lo quieres mapear de otra columna
                    'status'      => $this->mapStatus($old->reliability_status),
                    'observation' => $old->detail,
                    'cellphone'   => $old->phone,
                    'address'     => $old->address
                ]);
                $migrated++;
            } catch (\Throwable $e) {
                // Puedes loguear errores si quieres
                error_log($e);
                $failed++;
                continue;
            }
        }

        return response()->json([
            'message' => "Migración completada",
            'total'   => $oldClients->count(),
            'migrated' => $migrated,
            'failed'  => $failed
        ]);
    }

    private function mapStatus($value)
    {
        // Aquí puedes ajustar según cómo era tu lógica anterior
        $value = strtolower($value);
        return match(true) {
            str_contains($value, 'vip') => 'VIP',
            str_contains($value, 'no') => 'No Confiable',
            default => 'Confiable',
        };
    }
}
