<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingreso extends Model
{
    use SoftDeletes;

    protected $table = 'ingresos';

    protected $fillable = [
        'fecha', 'descripcion', 'metodo', 'monto', 'estado',
        'user_id', 'anulado_por', 'anulado_at', 'nota',
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function user()       { return $this->belongsTo(User::class); }
    public function anuladoPor() { return $this->belongsTo(User::class, 'anulado_por'); }
}
