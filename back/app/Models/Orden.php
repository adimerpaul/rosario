<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Orden extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'ordenes';

    protected $fillable = [
        'numero',
        'fecha_creacion',
        'fecha_entrega',
        'detalle',
        'celular',
        'costo_total',
        'tipo_pago',
        'adelanto',
        'saldo',
        'estado',
        'peso',
        'nota',
        'user_id',
        'kilates18',
        'cliente_id',
        'joya_id',
        'tipo',
        'foto_modelo',
        'monto',
        'metodo',
        'fecha',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        //        'user_id',
        //        'cliente_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }

    public function joya()
    {
        return $this->belongsTo(Joya::class, 'joya_id');
    }

    public function joyas()
    {
        return $this->belongsToMany(Joya::class, 'orden_joyas', 'orden_id', 'joya_id');
    }

    public function pagos()
    {
        return $this->hasMany(OrdenPago::class, 'orden_id');
    }

    public function almacenMovimientos()
    {
        return $this->hasMany(AlmacenMovimiento::class, 'orden_id');
    }

    protected $appends = ['totalPagos'];

    public function getTotalPagosAttribute()
    {
        return $this->pagos()->where('estado', 'Activo')->sum('monto');
    }
    //    public function syncMontosYEstado(){
    //        // NO recalcules 'adelanto' aquí
    //        $costo = (float) ($this->costo_total ?? 0);
    //        $adelanto = (float) ($this->adelanto ?? 0);
    //
    //        $this->saldo = max(0, $costo - $adelanto);
    //
    // //        if ($this->estado !== 'Cancelada') {
    // //            if ($this->saldo <= 0) {
    // //                $this->estado = 'Entregado';
    // //            } elseif ($this->estado === 'Entregado') {
    // //                $this->estado = 'Pendiente';
    // //            }
    // //        }
    //    }
    //    protected static function booted()
    //    {
    //        // Antes de guardar, recalcula montos en función de pagos y costo_total
    //        static::saving(function (Orden $orden) {
    //            // Si cambia costo_total o por cualquier update, mantenemos coherencia
    //            $orden->syncMontosYEstado();
    //        });
    //    }
}
