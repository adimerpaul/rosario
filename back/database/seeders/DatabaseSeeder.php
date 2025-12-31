<?php

namespace Database\Seeders;

use App\Models\Orden;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void{
//        $sqlFiles = [
//            'create_all_tables.sql',
////            'clients_insert_script.sql',
////            'loans_insert_script.sql',
////            'amounts_insert_script.sql',
////            'orders_insert_script.sql',
////            'loan_payments_insert_script.sql',
////            'loan_capital_payments_insert_script.sql',
////            'daily_histories_insert_script.sql',
//        ];
//
//        foreach ($sqlFiles as $file) {
//            $path = database_path('sql/' . $file);
//            if (File::exists($path)) {
//                DB::unprepared(File::get($path));
//            } else {
//                echo "Archivo no encontrado: $file\n";
//            }
//        }

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
//            User::create([
//                'name' => $usuario['name'],
//                'username' => $usuario['username'],
//                'password' => bcrypt('123456'),
//                'role' => 'Vendedor',
//            ]);
        }
        $cogs = [
            ['name' => 'Precio Compra', 'value' => 950.00, 'description' => 'Precio de compra del oro'],
            ['name' => 'Precio Venta', 'value' => 1200.00, 'description' => 'Precio de venta del oro'],
            ['name' => 'Prestamos para comprar', 'value' => 900, 'description' => 'Interes por prestamos para comprar oro'],
            ['name' => 'Tipo de cambio', 'value' => 6.96, 'description' => 'Tipo de cambio USD a BOB'],
        ];
        foreach ($cogs as $cog) {
            \App\Models\Cog::create($cog);
        }
//        exit();
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
//            \App\Models\Client::create([
//                'name' => $client['name'],
//                'ci' => $client['ci'],
//                'cellphone' => $client['cellphone'],
//                'status' => 'Confiable',
//            ]);
        }
//        Orden::create([
//            'numero' => 'O0001-2024',
//            'fecha_creacion' => now(),
//            'detalle' => 'Orden de prueba',
//            'celular' => '72466152',
//            'costo_total' => 100.00,
//            'adelanto' => 50.00,
//            'saldo' => 50.00,
//            'estado' => 'Pendiente',
//            'peso' => 0.5,
//            'nota' => 'Nota de prueba',
//            'user_id' => $user->id,
//            'cliente_id' => 1, // Asignar al primer cliente creado
//        ]);
    }
}
