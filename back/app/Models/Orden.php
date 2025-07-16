<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orden extends Model{
    use SoftDeletes;
    protected $table = 'ordenes';
    protected $fillable = [
        'numero',
        'fecha_creacion',
        'fecha_entrega',
        'detalle',
        'celular',
        'costo_total',
        'adelanto',
        'saldo',
        'estado',
        'peso',
        'nota',
        'user_id',
        'cliente_id'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'user_id',
        'cliente_id'
    ];
    function user(){
        return $this->belongsTo(User::class);
    }
    function cliente(){
        return $this->belongsTo(Client::class, 'cliente_id');
    }
}
