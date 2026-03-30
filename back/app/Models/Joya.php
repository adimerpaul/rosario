<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Joya extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'joyas';

    protected $fillable = [
        'tipo',
        'peso',
        'linea',
        'estuche_id',
        'user_id',
        'estuche',
        'nombre',
        'imagen',
        'monto_bs',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    public function estucheItem()
    {
        return $this->belongsTo(Estuche::class, 'estuche_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(Orden::class, 'joya_id');
    }

    public function ventasItems()
    {
        return $this->belongsToMany(Orden::class, 'orden_joyas', 'joya_id', 'orden_id');
    }
}
