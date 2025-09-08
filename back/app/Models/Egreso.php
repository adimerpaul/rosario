<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends Model
{
    use SoftDeletes;

    protected $table = 'egresos';

    protected $fillable = [
        'fecha','descripcion','metodo','monto','estado',
        'user_id','anulado_por','anulado_at','nota'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'anulado_at' => 'datetime',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function anuladoPor(){ return $this->belongsTo(User::class, 'anulado_por'); }
}
