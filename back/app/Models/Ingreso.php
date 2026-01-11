<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Ingreso extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'ingresos';

    protected $fillable = [
        'fecha', 'descripcion', 'metodo', 'monto', 'estado',
        'user_id', 'anulado_por', 'anulado_at', 'nota',
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function user()       { return $this->belongsTo(User::class); }
    public function anuladoPor() { return $this->belongsTo(User::class, 'anulado_por'); }
}
