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
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('descripcion');
            $table->enum('metodo', ['EFECTIVO', 'QR'])->default('EFECTIVO');
            $table->decimal('monto', 10, 2)->default(0);
            $table->enum('estado', ['Activo', 'Anulado'])->default('Activo');

            // quién creó / quién anuló
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('anulado_por')->nullable()->constrained('users');
            $table->timestamp('anulado_at')->nullable();
            $table->string('nota')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
