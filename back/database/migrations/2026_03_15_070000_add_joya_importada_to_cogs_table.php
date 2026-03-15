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
            ['name' => 'Joya importada'],
            [
                'value' => 1350.00,
                'description' => 'Precio de joya importada',
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
        DB::table('cogs')->where('name', 'Joya importada')->delete();
    }
};
