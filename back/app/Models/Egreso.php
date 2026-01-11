<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Egreso extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

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
