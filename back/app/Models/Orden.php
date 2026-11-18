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
        'precio_oro',
        'tipo_pago',
        'adelanto',
        'adelanto_efectivo',
        'adelanto_qr',
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

    protected static function booted(): void
    {
        static::saving(function (Orden $orden) {
            $orden->sincronizarAdelantoPorMetodo();
        });
    }

    /**
     * Mantiene adelanto = adelanto_efectivo + adelanto_qr.
     * Si se fijo el desglose, recalcula el total y el metodo principal;
     * si solo se cambio adelanto/tipo_pago, asigna todo al metodo de la orden.
     */
    public function sincronizarAdelantoPorMetodo(): void
    {
        $desgloseCambiado = $this->isDirty('adelanto_efectivo') || $this->isDirty('adelanto_qr');

        if ($desgloseCambiado) {
            $efectivo = round(max(0, (float) ($this->adelanto_efectivo ?? 0)), 2);
            $qr = round(max(0, (float) ($this->adelanto_qr ?? 0)), 2);
            $this->adelanto_efectivo = $efectivo;
            $this->adelanto_qr = $qr;
            $this->adelanto = round($efectivo + $qr, 2);
            if ($efectivo > 0 || $qr > 0) {
                $this->tipo_pago = $qr > $efectivo ? 'QR' : 'Efectivo';
            }

            return;
        }

        if ($this->exists && ! $this->isDirty('adelanto') && ! $this->isDirty('tipo_pago')) {
            return;
        }

        $adelanto = round((float) ($this->adelanto ?? 0), 2);
        $esQr = strtoupper(trim((string) $this->tipo_pago)) === 'QR';
        $this->adelanto_efectivo = $esQr ? 0 : $adelanto;
        $this->adelanto_qr = $esQr ? $adelanto : 0;
    }

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

    protected $appends = ['totalPagos', 'precio_oro_historico', 'costo_total_oro'];

    public function getTotalPagosAttribute()
    {
        return $this->pagos()->where('estado', 'Activo')->sum('monto');
    }

    public function getPrecioOroHistoricoAttribute()
    {
        if ($this->tipo !== 'Orden') {
            return $this->precio_oro !== null ? (float) $this->precio_oro : null;
        }

        return (float) ($this->precio_oro ?? 1080);
    }

    public function getCostoTotalOroAttribute(): float
    {
        if ($this->tipo !== 'Orden') {
            return (float) ($this->costo_total ?? 0);
        }

        return round(((float) ($this->peso ?? 0)) * ((float) $this->precio_oro_historico), 2);
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
