<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\AsignacionEstudiante;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Orden;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void{
        $user = User::create([
            'name' => 'Roger arias',
            'username' => 'admin',
            'password' => bcrypt('admin123Admin'),
            'role' => 'Administrador',
        ]);
        $usuarios = [
            ['name' => 'Jerson', 'username' => 'jerson'],
            ['name' => 'Alex', 'username' => 'alex'],
            ['name' => 'Jimmy', 'username' => 'jimmy'],
            ['name' => 'Andre', 'username' => 'andre'],
            ['name' => 'Edson', 'username' => 'edson'],
//            ['name' => 'Admin', 'username' => 'admin'],
            ['name' => 'Rogerito', 'username' => 'rogerito'],
            ['name' => 'Prueba', 'username' => 'prueba'],
        ];
        foreach ($usuarios as $usuario) {
            User::create([
                'name' => $usuario['name'],
                'username' => $usuario['username'],
                'password' => bcrypt('123456'),
                'role' => 'Vendedor',
            ]);
        }
        $clients = [
            ['name' => 'Juan Apaza Andrade', 'ci' => '765678', 'cellphone' => '72466152'],
            ['name' => 'Oscar Mallcu Rios', 'ci' => '7376363', 'cellphone' => '72309911'],
            ['name' => 'Maria Ayala Guzman', 'ci' => '9999999999', 'cellphone' => '72453540'],
            ['name' => 'Lino Tapia Mamani', 'ci' => '73646636', 'cellphone' => '71849662'],
            ['name' => 'Raul Reboso Sanga', 'ci' => '7568866', 'cellphone' => '62758581'],
            ['name' => 'Cristian Hugo Montaño León', 'ci' => '3558219', 'cellphone' => '76146106'],
            ['name' => 'Jose Veliz Bueno', 'ci' => '999999999', 'cellphone' => '68933410'],
            ['name' => 'Breneli Ardaya Mamani', 'ci' => '767765', 'cellphone' => '61815298'],
            ['name' => 'Lizhet Sahrela Vargas Flores', 'ci' => '7415633', 'cellphone' => '62755015'],
            ['name' => "Francisco Franco Chavez", "ci" => "99999999", "cellphone" => "77436125"],
        ];
        foreach ($clients as $client) {
            \App\Models\Client::create([
                'name' => $client['name'],
                'ci' => $client['ci'],
                'cellphone' => $client['cellphone'],
                'status' => 'Confiable',
            ]);
        }
//        Schema::create('ordenes', function (Blueprint $table) {
//            $table->id();
//            $table->string('numero')->unique();
//            $table->dateTime('fecha_creacion');
//            $table->dateTime('fecha_entrega')->nullable();
//            $table->text('detalle')->nullable();
//            $table->string('celular')->nullable();
//            $table->decimal('costo_total', 10, 2)->default(0.00);
//            $table->decimal('adelanto', 10, 2)->default(0.00);
//            $table->decimal('saldo', 10, 2)->default(0.00);
//            $table->string('estado')->default('Pendiente'); // Pendiente, Entregado, Cancelada
//            $table->decimal('peso', 8, 2)->default(0.00); // peso en kg
//            $table->text('nota')->nullable(); // nota adicional
//            $table->unsignedBigInteger('user_id');
//            $table->foreign('user_id')->references('id')->on('users');
//            $table->unsignedBigInteger('cliente_id')->nullable(); // Relación con el cliente, puede ser nulo si no hay cliente asociado
//            $table->foreign('cliente_id')->references('id')->on('clients')->onDelete('cascade'); // Aseguramos la relación con la tabla clientes
//            $table->softDeletes(); // Para manejar eliminaciones lógicas
//            $table->timestamps();
//        });
        Orden::create([
            'numero' => 'O001-2025',
            'fecha_creacion' => now(),
            'detalle' => 'Orden de prueba',
            'celular' => '72466152',
            'costo_total' => 100.00,
            'adelanto' => 50.00,
            'saldo' => 50.00,
            'estado' => 'Pendiente',
            'peso' => 0.5,
            'nota' => 'Nota de prueba',
            'user_id' => $user->id,
            'cliente_id' => 1, // Asignar al primer cliente creado
        ]);
    }
}
