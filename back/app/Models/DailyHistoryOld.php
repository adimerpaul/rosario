<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyHistoryOld extends Model
{
    protected $table = 'daily_histories';

    protected $fillable = [
        'amount',
        'description',
        'type',
        'user_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
