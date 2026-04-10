<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('prestamos', 'precio_compra_prestamo')) {
            return;
        }

        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn('precio_compra_prestamo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('prestamos', 'precio_compra_prestamo')) {
            return;
        }

        Schema::table('prestamos', function (Blueprint $table) {
            $table->decimal('precio_compra_prestamo', 12, 2)
                ->default(0)
                ->after('precio_oro');
        });
    }
};
