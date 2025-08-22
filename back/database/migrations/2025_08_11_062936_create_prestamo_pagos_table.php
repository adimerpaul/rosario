<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestamo_pagos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->default(now());
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('prestamo_id');
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 50)->default('Efectivo'); // Efectivo | Transferencia | Tarjeta | Otro
            $table->string('estado')->default('Activo'); // Activo | Anulado
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamo_pagos');
    }
};
