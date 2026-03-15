<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class VitrinaColumna extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'vitrina_columnas';

    protected $fillable = [
        'vitrina_id',
        'codigo',
        'orden',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function vitrina()
    {
        return $this->belongsTo(Vitrina::class);
    }

    public function estuches()
    {
        return $this->hasMany(Estuche::class, 'vitrina_columna_id')->orderBy('orden');
    }
}
