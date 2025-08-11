<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmountOld extends Model
{
    protected $table = 'amounts';

    protected $fillable = [
        'payment',
        'detail',
        'loan_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
