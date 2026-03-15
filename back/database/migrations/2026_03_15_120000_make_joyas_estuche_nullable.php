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

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE joyas MODIFY estuche VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('joyas')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('joyas')
            ->whereNull('estuche')
            ->update(['estuche' => '']);

        DB::statement("ALTER TABLE joyas MODIFY estuche VARCHAR(255) NOT NULL DEFAULT ''");
    }
};
