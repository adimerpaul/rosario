<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanOld extends Model
{
    protected $table = 'loans';

    protected $fillable = [
        'code',
        'weight',
        'jewelry_amount',
        'interest',
        'detail',
        'state',
        'user_id',
        'client_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
