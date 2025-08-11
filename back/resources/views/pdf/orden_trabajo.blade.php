<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de Trabajo</title>
    <style>
        @page { margin: 5mm; }
        body { font-family: DejaVu Sans, sans-serif; color:#111; }
        .wrap { width: 100%; margin: 0 auto; }
        .marco { border: 3px solid #c9302c; padding: 12px; border-radius: 8px; }
        .titulo { text-align: center; color:#c9302c; font-weight: 700; font-size: 18px; margin: 2px 0 8px; }
        .fila { display: flex; align-items: center; justify-content: space-between; }
        .sm { font-size: 11px; }
        .xs { font-size: 10px; }
        .bold { font-weight: bold; }
        .centro { text-align: center; }
        .derecha { text-align: right; }
        .separ { height: 6px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 4px 6px; font-size: 12px; }

        /* Estilo unificado de cajas */
        .grid2 { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .cell  { border: 2px solid #c9302c; border-radius: 8px; padding: 8px 10px; vertical-align: top; }
        .rowline { display: flex; justify-content: space-between; gap: 8px; }
        .lbl { min-width: 110px; font-weight: bold; display: inline-block; }
        .val { flex: 1; text-align: right; }
        .val-left { flex: 1; text-align: left; }

        /* Tabla key-value interna (montos) */
        .kv { width: 100%; border-collapse: separate; border-spacing: 0; }
        .kv td { padding: 3px 0; }
        .kv td.k { width: 45%; font-weight: bold; }
        .kv td.v { width: 55%; text-align: right; }

        /* Chip estado */
        .chip { display:inline-block; padding: 1px 8px; border-radius: 12px; color:#fff; font-size: 10px; }
        .orange { background:#f0ad4e; } .green{ background:#5cb85c;} .red{background:#d9534f;}
    </style>
</head>
<body>
<div class="wrap">
    <div class="marco">

        <!-- Encabezado -->
        <div class="fila">
            <table>
                <tr>
                    <td>
                        <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="width: 50px; height: auto;">
                    </td>
                    <td>
                        <div class="centro">
                            <div class="bold">{{ $empresa['nombre'] }}</div>
                            <div class="xs">{{ $empresa['direccion'] }}</div>
                            <div class="xs">Cel: {{ $empresa['cel'] }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="derecha sm">
                            <div><b>Bs.</b> {{ number_format($precioOro,2) }}</div>
                            <div>{{ $hoy->format('M d, Y') }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="titulo">ORDEN DE TRABAJO</div>
        <div class="centro xs" style="margin-top:-6px;">{{ $empresa['sucursal'] }}</div>
        <div class="separ"></div>

        <!-- N° / Fecha - Cliente -->
        <table class="grid2">
            <tr>
                <td class="cell">
                    <div class="rowline sm">
                        <span class="lbl">N°:</span>
                        <span class="val">{{ $orden->numero }}</span>
                    </div>
                    <div class="rowline sm" style="margin-top:4px">
                        <span class="lbl">Día / Mes / Año:</span>
                        <span class="val">{{ \Carbon\Carbon::parse($orden->fecha_creacion)->format('d / m / Y') }}</span>
                    </div>
                </td>
                <td class="cell">
                    <div class="rowline sm">
                        <span class="lbl">El Señor(a):</span>
                        <span class="val-left">{{ $orden->cliente->name ?? 'N/A' }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Trabajo de joya -->
        <table class="grid2">
            <tr>
                <td class="cell">
                    <div class="rowline sm">
                        <span class="lbl">Trabajo de joya:</span>
                        <span class="val-left">{{ $orden->detalle }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Montos (mismo formato de caja) -->
        <table class="grid2">
            <tr>
                <td class="cell">
                    <table class="kv">
                        <tr>
                            <td class="k">Peso fin. (kg)</td>
                            <td class="v">{{ number_format($orden->peso, 3) }}</td>
{{--                        </tr>--}}
{{--                        <tr>--}}
                            <td class="k">A cuenta</td>
                            <td class="v">{{ number_format($orden->adelanto, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="k">Precio Oro</td>
                            <td class="v">{{ number_format($precioOro, 2) }}</td>
{{--                        </tr>--}}
{{--                        <tr>--}}
                            <td class="k">Saldo</td>
                            <td class="v">{{ number_format($orden->saldo, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="k">Costo Total</td>
                            <td class="v">{{ number_format($orden->costo_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="k">Celular</td>
                            <td class="v">{{ $orden->celular }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Fecha Entrega + Estado -->
        <table class="grid2">
            <tr>
                <td class="cell">
                    <div class="rowline sm">
                        <span class="lbl">Fecha de Entrega:</span>
                        <span class="val-left bold">{{ \Carbon\Carbon::parse($orden->fecha_entrega)->format('d-m-Y') }}</span>
                    </div>
                    <div class="rowline sm" style="margin-top:6px">
                        <span class="lbl">Estado:</span>
                        <span class="val-left">
              <span class="chip {{ $orden->estado === 'Pendiente' ? 'orange' : ($orden->estado === 'Entregado' ? 'green' : 'red') }}">
                {{ $orden->estado }}
              </span>
            </span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Nota -->
        <table class="grid2">
            <tr>
                <td class="cell">
                    <div class="rowline sm" style="align-items:flex-start">
                        <span class="lbl">NOTA:</span>
                        <span class="val-left">
              {{ $orden->nota ?: 'Ningún trabajo será entregado sin esta orden. Pasado los 90 días de aviso no se hace responsable.' }}
            </span>
                    </div>
                </td>
            </tr>
        </table>

    </div>
</div>
</body>
</html>
