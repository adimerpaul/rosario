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
        if (Schema::hasTable('estuches')) {
            Schema::table('estuches', function (Blueprint $table) {
                $table->index('vitrina_columna_id');
                $table->dropUnique('estuches_vitrina_columna_id_unique');
            });
        }

        if (Schema::hasTable('joyas')) {
            Schema::table('joyas', function (Blueprint $table) {
                $table->index('estuche_id');
                $table->dropUnique('joyas_estuche_id_unique');
            });
        }

        if (Schema::hasTable('vitrinas') && DB::table('vitrinas')->count() > 3) {
            DB::table('vitrinas')
                ->whereIn('nombre', ['V4', 'V5', 'V chica 1', 'V chica 2', 'V chica 3', 'V chica 4', 'V chica 5'])
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('estuches')) {
            Schema::table('estuches', function (Blueprint $table) {
                $table->dropIndex(['vitrina_columna_id']);
                $table->unique('vitrina_columna_id');
            });
        }

        if (Schema::hasTable('joyas')) {
            Schema::table('joyas', function (Blueprint $table) {
                $table->dropIndex(['estuche_id']);
                $table->unique('estuche_id');
            });
        }
    }
};
