<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')->constrained('ordenes')->cascadeOnDelete();
            $table->foreignId('prestamo_id')->nullable()->constrained('prestamos')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo_movimiento', 20);
            $table->dateTime('fecha_movimiento');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['orden_id', 'id']);
            $table->index(['tipo_movimiento', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_movimientos');
    }
};
