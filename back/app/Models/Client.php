<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'ci',
        'carnet',
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
