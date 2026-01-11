<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class DailyCash extends Model implements AuditableContract{
    use SoftDeletes, AuditableTrait;

    protected $table = 'daily_cashes';
    protected $fillable = [
        'date','opening_amount','user_id','note','closed'
    ];
//    protected $casts = [
//        'date' => 'date',
//        'opening_amount' => 'decimal:2',
//        'closed' => 'boolean',
//    ];

    public function user(){ return $this->belongsTo(User::class); }
}
