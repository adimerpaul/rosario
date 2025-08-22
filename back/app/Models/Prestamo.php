<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestamo extends Model
{
    use SoftDeletes;

    protected $table = 'prestamos';

    protected $fillable = [
        'numero','fecha_creacion','fecha_limite','cliente_id','user_id',
        'peso','precio_oro','valor_total','valor_prestado',
        'interes','almacen', // <- asegúrate de incluirlo
        'saldo','celular','detalle','estado'
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function user(){ return $this->belongsTo(User::class); }
    public function cliente(){ return $this->belongsTo(Client::class, 'cliente_id'); }
    public function pagos(){ return $this->hasMany(PrestamoPago::class, 'prestamo_id'); }
}
