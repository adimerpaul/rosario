<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Garantía</title>
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

        /* Cajas unificadas */
        .grid2 { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .cell  { border: 2px solid #c9302c; border-radius: 8px; padding: 8px 10px; vertical-align: top; }
        .rowline { display: flex; justify-content: space-between; gap: 8px; }
        .lbl { min-width: 110px; font-weight: bold; display: inline-block; }
        .val { flex: 1; text-align: right; }
        .val-left { flex: 1; text-align: left; }

        /* Tabla key-value derecha */
        .kv { width: 100%; border-collapse: separate; border-spacing: 0; }
        .kv td { padding: 3px 0; }
        .kv td.k { width: 45%; font-weight: bold; }
        .kv td.v { width: 55%; text-align: right; }

        .borde-rojo { border:2px solid #c9302c; border-radius:8px; padding:6px 8px; }

        .nota { font-size: 11px; line-height: 1.25; text-align: justify; }
        .firma { margin-top: 22px; text-align: center; font-size: 11px; }
        .firma .line { margin: 20px auto 5px; width: 220px; border-top: 1px solid #444; }
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
                            <div>{{ \Carbon\Carbon::parse($garantia['fecha'])->format('d/m/Y') }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="titulo">GARANTÍA</div>
        <div class="centro xs" style="margin-top:-6px;">{{ $empresa['sucursal'] }}</div>
        <div class="separ"></div>

        <!-- Columna izq: logo/leyenda | Columna der: datos de la garantía -->
        <table class="grid2">
            <tr>
                <td class="cell" style="width:45%">
                    <div class="centro" style="margin-top:4px">
                        <div class="bold sm">{{ $empresa['nombre'] }}</div>
                        <div class="xs">Exclusiva en Joyas</div>
                        <div class="xs">Oro — 18 Kilates</div>
                    </div>
                    <div class="separ"></div>
                    <div class="nota xs">
                        {{ $empresa['nombre'] }} garantiza la autenticidad del material de sus piezas.
                        Esta garantía cubre <b>defectos de fabricación</b> durante el período indicado.
                    </div>
                </td>
                <td class="cell" style="width:55%">
                    <table class="kv">
                        <tr><td class="k">Código:</td>   <td class="v">{{ $garantia['codigo'] }}</td></tr>
                        <tr><td class="k">Fecha:</td>    <td class="v">{{ \Carbon\Carbon::parse($garantia['fecha'])->format('d/m/Y') }}</td></tr>
                        <tr><td class="k">Cliente:</td>  <td class="v">{{ $garantia['cliente'] }}</td></tr>
                        <tr><td class="k">Tipo:</td>     <td class="v">{{ $garantia['tipo'] }}</td></tr>
                        <tr><td class="k">Periodo:</td>  <td class="v">{{ $garantia['periodo'] }}</td></tr>
                    </table>

                    <div class="borde-rojo sm" style="margin-top:8px;">
                        <b>Detalle:</b> {{ $garantia['detalle'] }}
                    </div>

                    <div class="borde-rojo xs" style="margin-top:6px; text-align:center;">
                        Realizar mantenimiento <b>sin costo</b> por {{ $garantia['mantenimiento_meses'] }} meses
                        (limpieza, pulido y ajuste simples).
                    </div>
                </td>
            </tr>
        </table>

        <!-- Condiciones -->
        <table class="grid2">
            <tr>
                <td class="cell">
                    <div class="nota">
                        <b>Condiciones:</b>
                        Esta garantía <b>no</b> cubre daños por golpes, rayones, deformaciones por uso,
                        exposición a químicos, humedad o calor extremos, pérdida de piedras por impacto,
                        ni intervenciones de terceros (reparación o modificación no autorizada).
                        Es indispensable presentar este documento para hacer válida la garantía.
                    </div>
                    <div class="centro xs" style="margin-top:8px;">
                        “Gracias por su preferencia”
                    </div>
                </td>
            </tr>
        </table>

        <!-- Firmas -->
        <div class="firma">
            <div class="line"></div>
            <div>Firma y sello</div>
        </div>

    </div>
</div>
</body>
</html>
