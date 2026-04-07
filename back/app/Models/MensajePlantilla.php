<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajePlantilla extends Model
{
    protected $table = 'mensaje_plantillas';

    protected $fillable = [
        'clave',
        'nombre',
        'contenido',
    ];
}
