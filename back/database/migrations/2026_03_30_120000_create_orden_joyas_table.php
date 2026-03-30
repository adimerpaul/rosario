<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_joyas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')->constrained('ordenes')->cascadeOnDelete();
            $table->foreignId('joya_id')->constrained('joyas')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['orden_id', 'joya_id']);
        });

        DB::table('ordenes')
            ->whereNotNull('joya_id')
            ->orderBy('id')
            ->select(['id', 'joya_id', 'created_at', 'updated_at'])
            ->get()
            ->each(function ($orden) {
                DB::table('orden_joyas')->insert([
                    'orden_id' => $orden->id,
                    'joya_id' => $orden->joya_id,
                    'created_at' => $orden->created_at,
                    'updated_at' => $orden->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_joyas');
    }
};
