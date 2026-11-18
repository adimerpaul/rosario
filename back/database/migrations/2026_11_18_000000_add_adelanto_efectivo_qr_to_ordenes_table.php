<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->decimal('adelanto_efectivo', 12, 2)->default(0)->after('adelanto');
            $table->decimal('adelanto_qr', 12, 2)->default(0)->after('adelanto_efectivo');
        });

        // Repara los registros pasados: todo el adelanto va al metodo que tenia la orden.
        DB::table('ordenes')->update([
            'adelanto_qr' => DB::raw("CASE WHEN UPPER(TRIM(COALESCE(tipo_pago,''))) = 'QR' THEN COALESCE(adelanto,0) ELSE 0 END"),
            'adelanto_efectivo' => DB::raw("CASE WHEN UPPER(TRIM(COALESCE(tipo_pago,''))) = 'QR' THEN 0 ELSE COALESCE(adelanto,0) END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropColumn(['adelanto_efectivo', 'adelanto_qr']);
        });
    }
};
