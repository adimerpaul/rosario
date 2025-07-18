<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenPago extends Model{
    use SoftDeletes;
    protected $table = 'orden_pagos';
    protected $fillable = [
        'fecha',
        'user_id',
        'orden_id',
        'monto'
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
