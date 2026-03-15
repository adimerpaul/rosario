<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Estuche extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'estuches';

    protected $fillable = [
        'vitrina_columna_id',
        'nombre',
        'orden',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = ['ocupado'];

    public function columna()
    {
        return $this->belongsTo(VitrinaColumna::class, 'vitrina_columna_id');
    }

    public function joya()
    {
        return $this->hasOne(Joya::class);
    }

    public function getOcupadoAttribute(): bool
    {
        return $this->joya()->exists();
    }
}
