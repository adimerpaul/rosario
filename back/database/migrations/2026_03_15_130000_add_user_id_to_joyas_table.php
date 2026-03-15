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
        if (! Schema::hasTable('joyas')) {
            return;
        }

        Schema::table('joyas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('estuche_id')->constrained('users');
        });

        $adminId = DB::table('users')
            ->where('role', 'Administrador')
            ->orderBy('id')
            ->value('id');

        if ($adminId) {
            DB::table('joyas')
                ->whereNull('user_id')
                ->update(['user_id' => $adminId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('joyas')) {
            return;
        }

        Schema::table('joyas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
