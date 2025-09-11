<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    protected $table = 'prestamos';

    protected $fillable = [
        'numero',
        'fecha_creacion',
        'fecha_limite',
        'cliente_id',
        'user_id',
        'peso',         // kg bruto
        'merma',        // kg merma/piedra
        'peso_neto',    // kg neto (opcional si agregas la columna)
        'precio_oro',
        'valor_total',
        'valor_prestado',
        'interes',
        'almacen',
        'saldo',
        'celular',
        'detalle',
        'estado',
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function cliente() { return $this->belongsTo(Client::class, 'cliente_id'); }
    public function user()    { return $this->belongsTo(User::class, 'user_id'); }

    public function pagos()
    {
        return $this->hasMany(PrestamoPago::class, 'prestamo_id');
    }
}
