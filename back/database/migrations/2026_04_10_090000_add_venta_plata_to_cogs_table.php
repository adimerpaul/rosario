<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('cogs')->updateOrInsert(
            ['name' => 'Venta plata'],
            [
                'value' => 120.00,
                'description' => 'Monto de venta para plata',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cogs')->where('name', 'Venta plata')->delete();
    }
};
