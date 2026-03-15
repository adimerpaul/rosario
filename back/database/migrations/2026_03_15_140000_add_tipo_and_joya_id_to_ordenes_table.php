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
            if (! Schema::hasColumn('ordenes', 'tipo')) {
                $table->string('tipo')->default('Orden')->after('numero');
            }

            if (! Schema::hasColumn('ordenes', 'joya_id')) {
                $table->foreignId('joya_id')->nullable()->after('cliente_id')->constrained('joyas')->nullOnDelete();
            }
        });

        DB::table('ordenes')->whereNull('tipo')->update(['tipo' => 'Orden']);
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'joya_id')) {
                $table->dropConstrainedForeignId('joya_id');
            }

            if (Schema::hasColumn('ordenes', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
