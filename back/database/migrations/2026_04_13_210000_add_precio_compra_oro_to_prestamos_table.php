<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('prestamos')) {
            return;
        }

        if (! Schema::hasColumn('prestamos', 'precio_compra_oro')) {
            Schema::table('prestamos', function (Blueprint $table) {
                $table->decimal('precio_compra_oro', 12, 2)
                    ->default(0)
                    ->after('precio_oro');
            });
        }

        DB::table('prestamos')->update([
            'precio_compra_oro' => 820,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('prestamos') || ! Schema::hasColumn('prestamos', 'precio_compra_oro')) {
            return;
        }

        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn('precio_compra_oro');
        });
    }
};
