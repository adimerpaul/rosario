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
        'estuche',
        'nombre',
        'imagen',
        'monto_bs',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
