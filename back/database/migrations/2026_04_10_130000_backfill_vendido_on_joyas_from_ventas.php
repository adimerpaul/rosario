<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $joyaIdsDirectos = DB::table('ordenes')
            ->where('tipo', 'Venta directa')
            ->where('estado', '!=', 'Cancelada')
            ->whereNotNull('joya_id')
            ->pluck('joya_id');

        $joyaIdsPivot = DB::table('orden_joyas')
            ->join('ordenes', 'ordenes.id', '=', 'orden_joyas.orden_id')
            ->where('ordenes.tipo', 'Venta directa')
            ->where('ordenes.estado', '!=', 'Cancelada')
            ->pluck('orden_joyas.joya_id');

        $joyaIds = $joyaIdsDirectos
            ->merge($joyaIdsPivot)
            ->filter()
            ->unique()
            ->values();

        if ($joyaIds->isNotEmpty()) {
            DB::table('joyas')
                ->whereIn('id', $joyaIds)
                ->update(['vendido' => true]);
        }
    }

    public function down(): void
    {
        // Backfill irreversible: no-op.
    }
};
