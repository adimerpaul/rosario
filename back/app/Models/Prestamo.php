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
        'cliente_id',
        'user_id',
        'peso',         // kg bruto
        'merma',        // kg merma/piedra
        'peso_neto',    // kg neto (opcional)
        'precio_oro',
        'valor_total',
        'valor_prestado',
        'interes',      // % mensual
        'almacen',      // % mensual
        'saldo',        // se ignora al leer (se calcula), pero lo dejamos para compat.
        'celular',
        'detalle',
        'estado',
    ];

    protected $hidden = ['created_at','updated_at','deleted_at'];

    // Si quieres que la API también entregue campos auxiliares, descomenta:
    // protected $appends = ['dias_transcurridos','cargo_diario','cargos_acumulados'];

    public function cliente() { return $this->belongsTo(Client::class, 'cliente_id'); }
    public function user()    { return $this->belongsTo(User::class, 'user_id'); }
    public function pagos()   { return $this->hasMany(PrestamoPago::class, 'prestamo_id'); }

    /* =========================
       CÁLCULO DE SALDO DINÁMICO
       ========================= */

    // Convierte "saldo" guardado a saldo calculado al LEER el atributo
    public function getSaldoAttribute($stored)
    {
        $capital   = (float) ($this->valor_prestado ?? 0);
        if ($capital <= 0) return 0.0;

        // % mensual total (interés + almacén). Por ejemplo 3% + 2% = 5% mensual.
        $tasaMensual = (float) ($this->interes ?? 0) + (float) ($this->almacen ?? 0);

        // Días transcurridos desde la fecha de creación (incluye hoy = 0 días si es hoy)
        $fechaBase = $this->fecha_creacion ? Carbon::parse($this->fecha_creacion) : today();
        $dias = max(0, $fechaBase->diffInDays(today()));

        // Interés diario simple: (tasa mensual / 30). Si quieres 31/30 exacto, ajusta aquí.
        $tasaDiaria = $tasaMensual / 100 / 30;

        // Cargos acumulados hasta hoy (interés simple sobre el capital original)
        $cargos = round($capital * $tasaDiaria * $dias, 2);

        // Pagos activos
        $pagado = (float) $this->pagos()->where('estado','Activo')->sum('monto');

        // Saldo = capital + cargos - pagado
        $saldo = round($capital + $cargos - $pagado, 2);

        return $saldo > 0 ? $saldo : 0.0;
    }

    /* ====== (Opcional) Campos auxiliares para depurar o mostrar en la API ====== */
    public function getDiasTranscurridosAttribute()
    {
        $fechaBase = $this->fecha_creacion ? Carbon::parse($this->fecha_creacion) : today();
        return max(0, $fechaBase->diffInDays(today()));
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
}
