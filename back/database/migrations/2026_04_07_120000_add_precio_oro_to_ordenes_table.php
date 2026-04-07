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
            $table->decimal('precio_oro', 12, 2)->nullable()->after('peso');
        });

        DB::table('ordenes')
            ->where('tipo', 'Orden')
            ->whereNull('precio_oro')
            ->update(['precio_oro' => 1080]);
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropColumn('precio_oro');
        });
    }
};
