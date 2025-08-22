<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Garantía</title>
    <style>
        /* Página y tipografía (compacto) */
        @page { margin: 6mm; }
        body { font-family: DejaVu Sans, sans-serif; color:#111; }
        .wrap { width: 100%; }

        /* Paleta/utilidades */
        .brand{ color:#FF0000; }
        .xs{font-size:9px} .sm{font-size:10px} .md{font-size:11px} .lg{font-size:12px} .xl{font-size:16px}
        .bold{font-weight:700} .center{text-align:center} .right{text-align:right}
        .mt2{margin-top:2px} .mt4{margin-top:4px} .mt6{margin-top:6px}
        .w100{width:100%}

        /* Marco general */
        .sheet{ border:2.5px solid #FF0000; border-radius:14px; padding:10px; }

        /* Encabezado */
        table.head{ width:100%; border-collapse:collapse }
        table.head td{ vertical-align:middle }
        .logo{ width:58px; height:auto }
        .rings{ width:60px; height:auto }
        .title{ font-weight:800; font-size:17px; letter-spacing:.2px; }
        .sub{ font-size:9.5px; color:#555; margin-top:-2px }

        .pill-badge{               /* Bs. + fecha */
            display:inline-block; min-width:110px; text-align:center;
            border:2px solid #FF0000; border-radius:12px; padding:4px 8px;
            font-size:10px; line-height:1.1;
        }
        .pill-code{
            display:inline-block; margin-top:4px; border:2px solid #FF0000;
            border-radius:10px; padding:2px 8px; font-size:10px;
        }

        /* Grillas de cajas (muy compacto) */
        table.grid{ width:100%; border-collapse:separate; border-spacing:5px; }
        .cell{ border:2px solid #FF0000; border-radius:12px; padding:6px 8px; position:relative; }

        /* Etiqueta flotante */
        .label-float{
            position:absolute; top:-8px; left:12px; background:#fff; padding:0 6px;
            font-size:10px; color:#FF0000; font-weight:700; border-radius:8px;
        }

        /* Fecha en cajitas */
        .date-line{ margin-top:4px }
        .date-box{
            display:inline-block; border:1.6px solid #FF0000; border-radius:6px;
            padding:2px 6px; font-weight:700; font-size:10px; margin:0 2px;
        }

        /* Tabla key-value compacta */
        .kv{ width:100%; border-collapse:collapse }
        .kv td{ padding:2px 0; }
        .kv .k{ font-weight:700 }
        .kv .v{ text-align:right }

        /* Píldoras info */
        .pills{ display:flex; gap:6px; }
        .pills .item{
            border:2px solid #FF0000; border-radius:12px; padding:5px 8px;
            font-size:10.5px; font-weight:700; text-align:center; flex:1;
        }

        /* Nota/firmas */
        .dot{ border-top:1px dashed #bbb; margin:6px 0 }
        .sign{ width:48%; text-align:center }
        .sign-line{ border-top:1px solid #333; width:75%; height:1px; margin:18px auto 2px }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $f = \Carbon\Carbon::parse($garantia['fecha'] ?? now());
        $dia = $f->format('d'); $mes = $f->format('m'); $ano = $f->format('Y');
    @endphp

    <div class="sheet">
        <!-- Encabezado -->
        <table class="head">
            <tr>
                <td style="width:70px">
                    @if(file_exists(public_path('images/logo.png')))
                        <img class="logo" src="{{ public_path('images/logo.png') }}">
                    @endif
                </td>
                <td class="center">
                    <div class="brand bold lg">{{ $empresa['nombre'] ?? 'JOYERIA ROSARIO' }}</div>
                    <div class="xs">{{ $empresa['direccion'] ?? 'Calle Junín entre La Plata y Soria — Frente a mercado' }}</div>
                    <div class="xs">Cel: {{ $empresa['cel'] ?? '704-12345' }} — {{ strtoupper($empresa['sucursal'] ?? 'ORURO') }}</div>
                    <div class="brand title mt4">GARANTÍA</div>
                    <div class="sub">Cobertura por defectos de fabricación según condiciones indicadas</div>
                </td>
                <td class="right" style="width:150px">
                    <div class="pill-badge">
                        <b>Bs.</b> {{ number_format($precioOro,2) }}<br>
                        <span class="xs" style="color:#666">{{ $f->format('d/m/Y') }}</span>
                    </div>
                    <div class="pill-code">Código: {{ $garantia['codigo'] ?? '-' }}</div>
                    @if(file_exists(public_path('images/rings.png')))
                        <div class="mt4"><img class="rings" src="{{ public_path('images/rings.png') }}"></div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Cliente + Fecha en cajitas -->
        <table class="grid mt6">
            <tr>
                <td class="cell" style="width:55%">
                    <span class="label-float">Cliente</span>
                    <div class="md" style="margin-top:2px">{{ mb_strtoupper($garantia['cliente'] ?? 'N/A') }}</div>
                </td>
                <td class="cell" style="width:45%">
                    <span class="label-float">Fecha</span>
                    <div class="date-line">
                        <span class="sm bold">Día</span> <span class="date-box">{{ $dia }}</span>
                        <span class="sm bold">Mes</span> <span class="date-box">{{ $mes }}</span>
                        <span class="sm bold">Año</span> <span class="date-box">{{ $ano }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Detalle / Tipo / Periodo -->
        <div class="cell">
            <span class="label-float">Detalle de la pieza / trabajo</span>
            <div class="md" style="margin-top:2px">{{ $garantia['detalle'] ?? '—' }}</div>
            <div class="xs" style="color:#555; margin-top:3px">
                <b>Tipo:</b> {{ $garantia['tipo'] ?? '—' }} &nbsp; • &nbsp;
                <b>Periodo:</b> {{ $garantia['periodo'] ?? '—' }}
            </div>
        </div>

        <!-- Píldoras informativas -->
        <div class="pills mt6">
            <div class="item">Mantenimiento sin costo: {{ $garantia['mantenimiento_meses'] ?? 0 }} meses</div>
            <div class="item">Sucursal: {{ strtoupper($empresa['sucursal'] ?? '—') }}</div>
        </div>

        <!-- Condiciones -->
        <div class="cell mt6">
            <span class="label-float">Condiciones de Garantía</span>
            <div class="xs" style="margin-top:2px; line-height:1.35; text-align:justify">
                La garantía cubre <b>defectos de fabricación</b> del metal y soldaduras durante el periodo indicado.
                No cubre golpes, rayones, deformaciones por uso, exposición a químicos, humedad/temperatura extremas,
                pérdida de piedras por impacto ni intervenciones de terceros (reparación o modificación no autorizada).
                Es indispensable presentar este documento para hacer válida la garantía.
            </div>
            <div class="center xs" style="margin-top:6px">“Gracias por su preferencia”</div>
        </div>

        <!-- Firmas -->
        <div class="dot"></div>
        <table class="w100" style="border-collapse:collapse">
            <tr>
                <td class="sign">
                    <div class="sign-line"></div>
                    <div class="xs">Firma Cliente</div>
                </td>
                <td class="sign" style="text-align:right">
                    <div class="sign-line"></div>
                    <div class="xs">Firma y sello</div>
                </td>
            </tr>
        </table>

    </div>
</div>
</body>
</html>
