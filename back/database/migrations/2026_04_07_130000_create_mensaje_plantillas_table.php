<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensaje_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('nombre');
            $table->longText('contenido');
            $table->timestamps();
        });

        DB::table('mensaje_plantillas')->insert([
            [
                'clave' => 'prestamo_regularizacion',
                'nombre' => 'Regularizacion',
                'contenido' => 'Hola #NOMBRE#, su prestamo #PRESTAMO# ya esta fuera de tiempo. Vencio el #FECHA# y tiene #DIAS_RETRASO# dia(s) de retraso. Su saldo actual es Bs. #SALDO#. Por favor regularice hoy mismo su pago.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'prestamo_fundicion',
                'nombre' => 'Fundicion',
                'contenido' => 'Hola #NOMBRE#, su prestamo #PRESTAMO# continua retrasado. Vencio el #FECHA# y tiene #DIAS_RETRASO# dia(s) de retraso. Su saldo actual es Bs. #SALDO#. Si no regulariza el pago a la brevedad, la joya pasara a fundicion.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mensaje_plantillas');
    }
};
