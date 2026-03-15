<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('joyas')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE joyas ALTER COLUMN imagen SET DEFAULT 'joya.png'");
        }

        DB::table('joyas')
            ->where(function ($query) {
                $query->whereNull('imagen')
                    ->orWhere('imagen', 'default.png')
                    ->orWhere('imagen', 'defaultJoya.png');
            })
            ->update(['imagen' => 'joya.png']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('joyas')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE joyas ALTER COLUMN imagen SET DEFAULT 'default.png'");
        }

        DB::table('joyas')
            ->where('imagen', 'joya.png')
            ->update(['imagen' => 'default.png']);
    }
};
