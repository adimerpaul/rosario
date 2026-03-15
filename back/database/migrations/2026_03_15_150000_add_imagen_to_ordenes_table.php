<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (! Schema::hasColumn('ordenes', 'foto_modelo')) {
                $table->string('foto_modelo')->nullable()->after('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'foto_modelo')) {
                $table->dropColumn('foto_modelo');
            }
        });
    }
};
