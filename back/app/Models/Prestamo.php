<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Prestamo extends Model
{
    protected $table = 'prestamos';

    protected $fillable = [
        'numero',
        'fecha_creacion',
        'fecha_limite',
        'fecha_cancelacion',
        'cliente_id',
        'user_id',
        'peso',
        'merma',
        'peso_neto',
        'precio_oro',
        'valor_total',
        'valor_prestado',
        'interes',
        'almacen',
        'saldo',
        'celular',
        'detalle',
        'estado',
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected $appends = ['dias_transcurridos','cargo_diario','cargos_acumulados','total_deuda','deuda_interes'];

    public function cliente() { return $this->belongsTo(Client::class, 'cliente_id'); }
    public function user()    { return $this->belongsTo(User::class, 'user_id'); }
    public function pagos()   { return $this->hasMany(PrestamoPago::class, 'prestamo_id'); }

    /* =========================
       CÁLCULO DE SALDO DINÁMICO
       ========================= */

    public function getSaldoAttribute($stored)
    {
        $capital = (float) ($this->valor_prestado ?? 0);
        if ($capital <= 0) return 0.0;

        // tasa mensual total
        $tasaMensual = (float) ($this->interes ?? 0) + (float) ($this->almacen ?? 0);
        $tasaDiaria  = $tasaMensual / 100 / 30;

        // 🔑 regla: si no hay pagos activos → contar desde fecha_creacion
        //           si hay al menos 1 pago activo → contar desde fecha_limite
        $tienePagos = $this->pagos()->where('estado', 'Activo')->exists();

        if ($tienePagos && $this->fecha_limite) {
            $fechaBase = Carbon::parse($this->fecha_limite);
        } else {
            $fechaBase = $this->fecha_creacion ? Carbon::parse($this->fecha_creacion) : today();
        }
//        error_log('fechaBase: ' . $fechaBase->toDateString());

        $dias = max(0, $fechaBase->diffInDays(today()));
//        error_log('dias: ' . $dias);

        // cargos
        $cargos = round($capital * $tasaDiaria * $dias, 2);

        // pagado
        $pagado = (float) $this->pagos()
            ->where('estado','Activo')
            ->whereIn('tipo_pago', ['TOTAL', 'SALDO'])
            ->sum('monto');

        $saldo = round($capital + $cargos - $pagado, 2);
        return $saldo > 0 ? $saldo : 0.0;
    }

    function getTotalDeudaAttribute(){
//        pago sea total o saldo de los prastmos_pagos
        $totalPagado = (float) $this->pagos()
            ->where('estado','Activo')
            ->whereIn('tipo_pago', ['TOTAL', 'SALDO'])
            ->sum('monto');
        return round(($this->valor_prestado ?? 0) - $totalPagado, 2);
    }

    public function getDiasTranscurridosAttribute(){
        $tienePagos = $this->pagos()->where('estado', 'Activo')->exists();
        if ($tienePagos && $this->fecha_limite) {
            $fechaBase = Carbon::parse($this->fecha_limite);
        } else {
            $fechaBase = $this->fecha_creacion ? Carbon::parse($this->fecha_creacion) : today();
        }
        $dias = $fechaBase->diffInDays(today());
        $dias ++;
        return round($dias, 0);
    }

    public function getCargoDiarioAttribute()
    {
        $capital     = (float) ($this->valor_prestado ?? 0);
        $tasaMensual = (float) ($this->interes ?? 0) + (float) ($this->almacen ?? 0);
        $tasaDiaria  = $tasaMensual / 100 / 30;
        return round($capital * $tasaDiaria, 2);
    }

    public function getCargosAcumuladosAttribute()
    {
        return round($this->cargo_diario * $this->dias_transcurridos, 2);
    }
    public function getDeudaInteresAttribute()
    {
        $tienePagos = $this->pagos()->where('estado', 'Activo')->exists();
        if ($tienePagos && $this->fecha_limite) {
            $fechaBase = Carbon::parse($this->fecha_limite);
        } else {
            $fechaBase = $this->fecha_creacion ? Carbon::parse($this->fecha_creacion) : today();
        }
        $dias = $fechaBase->diffInDays(today());
        $interesDiario = $this->total_deuda * (($this->interes + $this->almacen) / 100);
        $interesDiario = $interesDiario / 30;
        return round($interesDiario * $dias, 2);
    }
}
