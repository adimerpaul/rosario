<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\AsignacionEstudiante;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
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
//        protected $fillable = [
//            'name',
//            'ci',
//            'carnet',
//            'status',
//            'observation',
//            'cellphone',
//            'address'
//        ];
//        <div class="scrollable container">
//        <div class="row">
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        JUAN APAZA ANDRADE
//        <a href="https://joyasrosario.com/client/edit/6025" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 765678</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 72466152</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 700</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        OSCAR MALLCU RIOS
//        <a href="https://joyasrosario.com/client/edit/6024" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 7376363</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 72309911</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 3000</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        MARIA AYALA GUZMAN
//        <a href="https://joyasrosario.com/client/edit/6023" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 9999999999</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 72453540</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 500</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        LINO TAPIA MAMANI
//        <a href="https://joyasrosario.com/client/edit/6022" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 73646636</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 71849662</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 1000</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        RAUL REBOSO SANGA
//        <a href="https://joyasrosario.com/client/edit/6021" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-danger"><b>Estado: NO CONFIABLE - POR FUNDIDO</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 7568866</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 62758581</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 1500</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        CRISTHIAN HUGO MONTAñO LEóN
//        <a href="https://joyasrosario.com/client/edit/6020" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 3558219</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 76146106</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 0</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        JOSE VELIZ BUENO
//        <a href="https://joyasrosario.com/client/edit/6019" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 999999999</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 68933410</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 9760</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        BRENELI ARDAYA MAMANI
//        <a href="https://joyasrosario.com/client/edit/6018" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 767765</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 61815298</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 1000</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        LIZHET SAHRELA VARGAS FLORES
//        <a href="https://joyasrosario.com/client/edit/6017" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 7415633</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 62755015</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 0</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                    <div class="col-md-4">
//                <div class="card mt-3 shadow-lg">
//                    <div class="card-header bg-danger text-light">
//        FRANCISCO FRANCO CHAVEZ
//        <a href="https://joyasrosario.com/client/edit/6016" class="btn">
//                            <img src="/img/edit.png" height="25" width="25">
//                        </a>
//                    </div>
//                    <ul class="list-group small">
//                                                    <li class="list-group-item text-success"><b>Estado: CONFIABLE</b></li>
//                                                <li class="list-group-item"><b>CI:</b> 99999999</li>
//                        <li class="list-group-item"><b>Teléfono:</b> 77436125</li>
//                        <li class="list-group-item"><b>Dirección:</b> </li>
//                        <li class="list-group-item"><b>Ordenes :</b> Bs. 860</li>
//                        <li class="list-group-item"><b>Prestamos:</b> Bs. 0 </li>
//                    </ul>
//                </div>
//            </div>
//                </div>
//    </div>
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
    }
}
