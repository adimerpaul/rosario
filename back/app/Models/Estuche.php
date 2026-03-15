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

    protected $appends = ['ocupado', 'joyas_count'];

    public function columna()
    {
        return $this->belongsTo(VitrinaColumna::class, 'vitrina_columna_id');
    }

    public function joyas()
    {
        return $this->hasMany(Joya::class);
    }

    public function getOcupadoAttribute(): bool
    {
        return $this->joyas()->exists();
    }

    public function getJoyasCountAttribute(): int
    {
        return $this->joyas()->count();
    }
}
