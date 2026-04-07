<?php

namespace App\Http\Controllers;

use App\Models\MensajePlantilla;
use Illuminate\Http\Request;

class MensajePlantillaController extends Controller
{
    private const PLANTILLAS = [
        'prestamo_regularizacion' => [
            'nombre' => 'Regularizacion',
            'contenido' => 'Hola #NOMBRE#, su prestamo #PRESTAMO# ya esta fuera de tiempo. Vencio el #FECHA# y tiene #DIAS_RETRASO# dia(s) de retraso. Su saldo actual es Bs. #SALDO#. Por favor regularice hoy mismo su pago.',
        ],
        'prestamo_fundicion' => [
            'nombre' => 'Fundicion',
            'contenido' => 'Hola #NOMBRE#, su prestamo #PRESTAMO# continua retrasado. Vencio el #FECHA# y tiene #DIAS_RETRASO# dia(s) de retraso. Su saldo actual es Bs. #SALDO#. Si no regulariza el pago a la brevedad, la joya pasara a fundicion.',
        ],
    ];

    public function index()
    {
        return response()->json([
            'data' => $this->ensureTemplates()->values(),
            'placeholders' => [
                '#NOMBRE#',
                '#PRESTAMO#',
                '#FECHA#',
                '#DIAS_RETRASO#',
                '#SALDO#',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mensajes' => 'required|array',
            'mensajes.prestamo_regularizacion' => 'required|string',
            'mensajes.prestamo_fundicion' => 'required|string',
        ]);

        foreach (self::PLANTILLAS as $clave => $config) {
            MensajePlantilla::updateOrCreate(
                ['clave' => $clave],
                [
                    'nombre' => $config['nombre'],
                    'contenido' => trim($data['mensajes'][$clave]),
                ]
            );
        }

        return response()->json([
            'message' => 'Mensajes actualizados correctamente',
            'data' => $this->ensureTemplates()->values(),
        ]);
    }

    private function ensureTemplates()
    {
        foreach (self::PLANTILLAS as $clave => $config) {
            MensajePlantilla::firstOrCreate(
                ['clave' => $clave],
                [
                    'nombre' => $config['nombre'],
                    'contenido' => $config['contenido'],
                ]
            );
        }

        return MensajePlantilla::query()
            ->whereIn('clave', array_keys(self::PLANTILLAS))
            ->orderByRaw("CASE clave WHEN 'prestamo_regularizacion' THEN 1 WHEN 'prestamo_fundicion' THEN 2 ELSE 99 END")
            ->get();
    }
}
