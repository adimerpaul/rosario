<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class PrestamoPago extends Model  implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'prestamo_pagos';

    protected $fillable = [
        'fecha','user_id','prestamo_id','monto','estado',
        'metodo','tipo_pago' // <- NUEVOS CAMPOS
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function user(){ return $this->belongsTo(User::class); }
    public function prestamo(){ return $this->belongsTo(Prestamo::class, 'prestamo_id'); }
}
