<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Desglose del dinero entregado al otorgar el prestamo (efectivo / QR).
     * Ambos en 0 significa "sin desglose": todo se toma como EFECTIVO.
     */
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->decimal('entrega_efectivo', 12, 2)->default(0)->after('valor_prestado');
            $table->decimal('entrega_qr', 12, 2)->default(0)->after('entrega_efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn(['entrega_efectivo', 'entrega_qr']);
        });
    }
};
