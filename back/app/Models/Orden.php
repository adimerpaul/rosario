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
//        'user_id',
//        'cliente_id'
    ];
    function user(){
        return $this->belongsTo(User::class);
    }
    function cliente(){
        return $this->belongsTo(Client::class, 'cliente_id');
    }
    public function pagos()
    {
        return $this->hasMany(OrdenPago::class, 'orden_id');
    }
    public function syncMontosYEstado(){
        // NO recalcules 'adelanto' aquí
        $costo = (float) ($this->costo_total ?? 0);
        $adelanto = (float) ($this->adelanto ?? 0);

        $this->saldo = max(0, $costo - $adelanto);

//        if ($this->estado !== 'Cancelada') {
//            if ($this->saldo <= 0) {
//                $this->estado = 'Entregado';
//            } elseif ($this->estado === 'Entregado') {
//                $this->estado = 'Pendiente';
//            }
//        }
    }
    protected static function booted()
    {
        // Antes de guardar, recalcula montos en función de pagos y costo_total
        static::saving(function (Orden $orden) {
            // Si cambia costo_total o por cualquier update, mantenemos coherencia
            $orden->syncMontosYEstado();
        });
    }
}
