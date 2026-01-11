<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Client extends Model implements AuditableContract{
    use SoftDeletes, AuditableTrait;
    protected $table = 'clientes';
    protected $fillable = [
        'name',
        'ci',
        'status',
        'observation',
        'cellphone',
        'address'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
