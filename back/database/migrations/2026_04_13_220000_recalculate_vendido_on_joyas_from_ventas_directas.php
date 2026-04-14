<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('joyas') || ! Schema::hasTable('ordenes')) {
            return;
        }

        DB::table('joyas')->update(['vendido' => false]);

        $joyaIdsDirectos = DB::table('ordenes')
            ->where('tipo', 'Venta directa')
            ->where('estado', 'Entregado')
            ->whereNull('deleted_at')
            ->whereNotNull('joya_id')
            ->pluck('joya_id');

        $joyaIdsPivot = collect();

        if (Schema::hasTable('orden_joyas')) {
            $joyaIdsPivot = DB::table('orden_joyas')
                ->join('ordenes', 'ordenes.id', '=', 'orden_joyas.orden_id')
                ->where('ordenes.tipo', 'Venta directa')
                ->where('ordenes.estado', 'Entregado')
                ->whereNull('ordenes.deleted_at')
                ->pluck('orden_joyas.joya_id');
        }

        $joyaIdsVendidos = $joyaIdsDirectos
            ->merge($joyaIdsPivot)
            ->filter()
            ->unique()
            ->values();

        if ($joyaIdsVendidos->isNotEmpty()) {
            DB::table('joyas')
                ->whereIn('id', $joyaIdsVendidos)
                ->update(['vendido' => true]);
        }
    }

    public function down(): void
    {
        // Recalculo irreversible: no-op.
    }
};
