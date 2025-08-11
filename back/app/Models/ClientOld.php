<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientOld extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'ci',
        'first_name',
        'last_name',
        'address',
        'phone',
        'reliability_status',
        'status',
        'detail'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
