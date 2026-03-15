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
        Schema::create('vitrinas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vitrina_columnas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vitrina_id')->constrained('vitrinas');
            $table->string('codigo')->unique();
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('estuches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vitrina_columna_id')->unique()->constrained('vitrina_columnas');
            $table->string('nombre');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('joyas', function (Blueprint $table) {
            $table->foreignId('estuche_id')->nullable()->unique()->after('linea')->constrained('estuches');
        });

        $vitrinas = [
            'V1',
            'V2',
            'V3',
            'V4',
            'V5',
//            'V chica 1',
//            'V chica 2',
//            'V chica 3',
//            'V chica 4',
//            'V chica 5',
        ];

        $joyas = DB::table('joyas')->orderBy('id')->get()->values();
        $joyaIndex = 0;

        foreach ($vitrinas as $vitrinaIndex => $nombreVitrina) {
            $vitrinaId = DB::table('vitrinas')->insertGetId([
                'nombre' => $nombreVitrina,
                'orden' => $vitrinaIndex + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $prefijo = str_starts_with($nombreVitrina, 'V chica')
                ? 'C'.trim(str_replace('V chica', '', $nombreVitrina))
                : str_replace('V', '', $nombreVitrina);

            for ($i = 1; $i <= 5; $i++) {
                $codigo = $prefijo.'.'.$i;

                $columnaId = DB::table('vitrina_columnas')->insertGetId([
                    'vitrina_id' => $vitrinaId,
                    'codigo' => $codigo,
                    'orden' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $joya = $joyas->get($joyaIndex);
                $nombreEstuche = $joya?->estuche ?: 'ESTUCHE '.$codigo;

                $estucheId = DB::table('estuches')->insertGetId([
                    'vitrina_columna_id' => $columnaId,
                    'nombre' => $nombreEstuche,
                    'orden' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($joya) {
                    DB::table('joyas')
                        ->where('id', $joya->id)
                        ->update([
                            'estuche_id' => $estucheId,
                            'estuche' => $nombreEstuche,
                        ]);

                    $joyaIndex++;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('joyas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estuche_id');
        });

        Schema::dropIfExists('estuches');
        Schema::dropIfExists('vitrina_columnas');
        Schema::dropIfExists('vitrinas');
    }
};
