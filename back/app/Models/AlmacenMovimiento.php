<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AlmacenMovimiento extends Model implements AuditableContract
{
    use AuditableTrait;

    protected $table = 'almacen_movimientos';

    protected $fillable = [
        'orden_id',
        'prestamo_id',
        'user_id',
        'tipo_movimiento',
        'fecha_movimiento',
        'observacion',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
    ];

    protected $hidden = [
        'updated_at',
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class);
    }

    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
