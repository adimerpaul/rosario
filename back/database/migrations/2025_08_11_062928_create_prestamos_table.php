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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable();                 // se completa después de crear
            $table->date('fecha_creacion')->default(now());       // día del préstamo
            $table->date('fecha_limite')->nullable();             // fecha de vencimiento
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('user_id');

            $table->decimal('peso', 10, 3)->default(0);           // kg
            $table->decimal('precio_oro', 12, 2)->default(0);     // de cogs id=1
            $table->decimal('valor_total', 12, 2)->default(0);    // peso * precio_oro (referencial)
            $table->decimal('valor_prestado', 12, 2)->default(0); // efectivo entregado
            $table->decimal('interes', 12, 2)->default(0);        // interés total (monto)
            $table->decimal('saldo', 12, 2)->default(0);          // (prestado+interes) - pagos
            $table->string('celular')->nullable();
            $table->text('detalle')->nullable();
            $table->string('estado')->default('Pendiente');       // Pendiente | Pagado | Cancelado | Vencido
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
