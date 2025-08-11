<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrestamoPago extends Model
{
    use SoftDeletes;

    protected $table = 'prestamo_pagos';

    protected $fillable = ['fecha','user_id','prestamo_id','monto','estado'];

    protected $hidden = ['created_at','updated_at','deleted_at'];

//    protected $casts = [
//        'fecha' => 'date',
//        'monto' => 'decimal:2'
//    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function prestamo(){ return $this->belongsTo(Prestamo::class, 'prestamo_id'); }
}
