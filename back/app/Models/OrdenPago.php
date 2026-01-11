<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class OrdenPago extends Model implements AuditableContract{
    use SoftDeletes, AuditableTrait;
    protected $table = 'orden_pagos';
    protected $fillable = [
        'fecha',
        'user_id',
        'orden_id',
        'monto',
        'estado',
        'metodo'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    function user(){
        return $this->belongsTo(User::class);
    }
    function orden(){
        return $this->belongsTo(Orden::class, 'orden_id');
    }
}
