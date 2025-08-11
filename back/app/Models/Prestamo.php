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
        'peso','precio_oro','valor_total','valor_prestado','interes','saldo',
        'celular','detalle','estado'
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

//    protected $casts = [
//        'fecha_creacion' => 'date',
//        'fecha_limite'   => 'date',
//        'peso'           => 'decimal:3',
//        'precio_oro'     => 'decimal:2',
//        'valor_total'    => 'decimal:2',
//        'valor_prestado' => 'decimal:2',
//        'interes'        => 'decimal:2',
//        'saldo'          => 'decimal:2',
//    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function cliente(){ return $this->belongsTo(Client::class, 'cliente_id'); }
    public function pagos(){ return $this->hasMany(PrestamoPago::class, 'prestamo_id'); }
}
