<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Vitrina extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'vitrinas';

    protected $fillable = [
        'nombre',
        'orden',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function columnas()
    {
        return $this->hasMany(VitrinaColumna::class)->orderBy('orden');
    }
}
