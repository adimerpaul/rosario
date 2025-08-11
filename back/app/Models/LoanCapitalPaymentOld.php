<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCapitalPaymentOld extends Model
{
    protected $table = 'loan_capital_payments';

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
