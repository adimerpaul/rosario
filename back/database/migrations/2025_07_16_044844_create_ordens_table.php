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
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable();
            $table->dateTime('fecha_creacion');
            $table->date('fecha_entrega')->nullable();
            $table->text('detalle')->nullable()->default('');
            $table->string('celular')->nullable();
            $table->decimal('costo_total', 10, 2)->default(0.00);
            $table->decimal('adelanto', 10, 2)->default(0.00);
            $table->decimal('saldo', 10, 2)->default(0.00);
            $table->string('estado')->default('Pendiente'); // Pendiente, Entregado, Cancelada
            $table->decimal('peso', 8, 2)->default(0.00); // peso en kg
            $table->string('tipo_pago')->nullable(); // Efectivo, Tarjeta, Transferencia, Mixto
            $table->text('nota')->nullable()->default('');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('cliente_id')->nullable(); // Relación con el cliente, puede ser nulo si no hay cliente asociado
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade'); // Aseguramos la relación con la tabla clientes
            $table->softDeletes(); // Para manejar eliminaciones lógicas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
