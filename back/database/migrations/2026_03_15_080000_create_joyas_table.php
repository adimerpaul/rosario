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
        Schema::create('joyas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->decimal('peso', 10, 2)->default(0);
            $table->string('linea');
            $table->string('estuche')->nullable();
            $table->string('nombre');
            $table->string('imagen')->nullable()->default('joya.png');
            $table->decimal('monto_bs', 12, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('joyas')->insert([
            [
                'tipo' => 'Importada',
                'peso' => 5.00,
                'linea' => 'Mama',
                'estuche' => 'ESTUCHE 15 ANOS 2',
                'nombre' => 'ANILLO MUJER ROSADO',
                'imagen' => 'joya.png',
                'monto_bs' => 5400.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Joya nacional',
                'peso' => 7.50,
                'linea' => 'Papa',
                'estuche' => 'ESTUCHE PRINCIPAL 1',
                'nombre' => 'CADENA HOMBRE CLASICA',
                'imagen' => 'joya.png',
                'monto_bs' => 4200.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Plata',
                'peso' => 3.20,
                'linea' => 'Roger',
                'estuche' => 'ESTUCHE PLATA A',
                'nombre' => 'ANILLO PLATA CORAZON',
                'imagen' => 'joya.png',
                'monto_bs' => 450.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Importada',
                'peso' => 4.10,
                'linea' => 'Andreina',
                'estuche' => 'ESTUCHE NUEVO B',
                'nombre' => 'DIJE FLOR BRILLANTE',
                'imagen' => 'joya.png',
                'monto_bs' => 3800.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Joya nacional',
                'peso' => 6.30,
                'linea' => 'Mama',
                'estuche' => 'ESTUCHE VITRINA 3',
                'nombre' => 'ARETES MODELO LUNA',
                'imagen' => 'joya.png',
                'monto_bs' => 2950.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('joyas');
    }
};
