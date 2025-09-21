<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de Trabajo</title>
    <style>
        /* Página y tipografía (compacto) */
        @page { margin: 6mm; }               /* margen chico */
        body { font-family: DejaVu Sans, sans-serif; color:#111; }
        .wrap { width: 100%; }

        /* Paleta/utilidades */
        .brand{ color:#FF0000; }
        .xs{font-size:9px} .sm{font-size:10px} .md{font-size:11px} .lg{font-size:12px} .xl{font-size:16px}
        .bold{font-weight:700} .center{text-align:center} .right{text-align:right}
        .mt2{margin-top:2px} .mt4{margin:4px} .mt6{margin-top:6px}
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

        .pill-badge{               /* Bs. en pastilla */
            display:inline-block; min-width:110px; text-align:center;
            border:2px solid #FF0000; border-radius:12px; padding:2px 2px;
            font-size:10px; line-height:1.1;
        }
        .pill-nro{
            display:inline-block; margin-top:4px; border:1.5px solid #FF0000;
            border-radius:10px; padding:1px 4px; font-size:10px;
        }

        /* Grillas de cajas (muy compacto) */
        table.grid{ width:100%; border-collapse:separate; border-spacing:5px; }
        .cell{ border:1.5px solid #FF0000; border-radius:12px; padding:3px 4px; position:relative; }

        /* Etiqueta flotante (El Señor, Trabajo…) */
        .label-float{
            position:absolute; top:-8px; left:12px; background:#fff; padding:0 4px;
            font-size:10px; color:#FF0000; font-weight:700; border-radius:8px;
        }

        /* Fecha en cajitas (Día Mes Año) */
        .date-line{ margin-top:4px }
        .date-box{
            display:inline-block; border:1.6px solid #FF0000; border-radius:6px;
            padding:2px 6px; font-weight:700; font-size:10px; margin:0 2px;
        }

        /* Fila Por/A cuenta/Saldo */
        .resume{ display:flex; gap:6px; }
        .resume .item{
            border:2px solid #FF0000; border-radius:12px; padding:5px 8px;
            font-size:11px; font-weight:700; text-align:center; flex:1;
        }

        /* Tabla de montos/estado */
        .kv{ width:100%; border-collapse:collapse }
        .kv td{ padding:2px 0; }
        .kv .k{ font-weight:700; }
        .kv .v{ text-align:right }

        /* Chip estado */
        .chip{ display:inline-block; padding:1px 9px; border-radius:12px; color:#fff; font-size:9.5px; }
        .orange{ background:#f0ad4e } .green{ background:#5cb85c } .red{ background:#d9534f }

        /* Línea/firmas compactas */
        .dot{ border-top:1px dashed #bbb; margin:6px 0 }
        .sign{ width:48%; text-align:center }
        .sign-line{ border-top:1px solid #333; width:75%; height:1px; margin:18px auto 2px }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $estado = $orden->estado ?? 'Pendiente';
        $chip = $estado === 'Pendiente' ? 'orange' : ($estado === 'Entregado' ? 'green' : 'red');
        $fc = \Carbon\Carbon::parse($orden->fecha_creacion);
        $dia = $fc->format('d'); $mes = $fc->format('m'); $ano = $fc->format('Y');
        $entrega = $orden->fecha_entrega ? \Carbon\Carbon::parse($orden->fecha_entrega)->format('d-m-Y') : '—';
    @endphp

{{--    for por 2--}}
    @for($i=0; $i<2; $i++)
        <div class="sheet"style="padding:10px; margin-bottom:12px">
            <!-- Encabezado -->
            <table class="head">
                <tr>
                    <td style="width:70px;vertical-align: top;">
                        <img class="logo" src="{{ public_path('images/logo.png') }}">
                    </td>
                    <td class="center">
                        <div class="brand bold lg">{{ $empresa['nombre'] ?? 'JOYERIA ROSARIO' }}</div>
                        @if(file_exists(public_path('images/rings.png')))
                            <div class="mt4"><img class="rings" src="{{ public_path('images/rings.png') }}"></div>
                        @endif
                        <div class="xs">{{ $empresa['direccion'] ?? 'Calle Junín entre La Plata y Soria — Frente a mercado' }}</div>
                        <div class="xs">Cel: {{ $empresa['cel'] ?? '704-12345' }} — {{ strtoupper($empresa['sucursal'] ?? 'ORURO') }}</div>
                        <div class="brand title mt4">ORDEN DE TRABAJO</div>
                        <div class="sub">
                            DIR. ADOLFO MIER, POTOSÍ Y PAGADOR <br>
                            (LADO PALACE HOTEL) <br>
                            CEL. 73800584 TELF. 52-55713<br>
                            Oruro - Bolivia<br>
                        </div>
                    </td>
                    <td class="right" style="width:150px;vertical-align: top;">
                        <div class="pill-badge"><b>Bs.</b> {{ number_format($precioOro,2) }}<br><span class="xs" style="color:#666">{{ $hoy->format('d/m/Y') }}</span></div>
                        <div class="pill-nro">Nro: {{ $orden->numero }}</div>
                    </td>
                </tr>
            </table>

            <!-- N° + Fecha (cajitas) y Cliente -->
            <table class="grid mt6">
                <tr>
                    <td class="cell" style="width:50%">
                        <span class="label-float">N° y Fecha</span>
                        <div class="md"><b>N°:</b> {{ $orden->numero }}</div>
                        <div class="date-line">
                            <span class="sm bold">Día</span> <span class="date-box">{{ $dia }}</span>
                            <span class="sm bold">Mes</span> <span class="date-box">{{ $mes }}</span>
                            <span class="sm bold">Año</span> <span class="date-box">{{ $ano }}</span>
                        </div>
                    </td>
                    <td class="cell" style="width:50%">
                        <span class="label-float">El Señor:</span>
                        <div class="md" style="margin-top:2px">{{ mb_strtoupper($orden->cliente->name ?? 'N/A') }}</div>
                    </td>
                </tr>
            </table>

            <!-- Trabajo de Joya -->
            <div class="cell">
                <span class="label-float">Trabajo de Joya:</span>
                <div class="md" style="margin-top:2px">{{ $orden->detalle }}</div>
            </div>

            <div class="cell mt6">
                <span class="label-float">Montos</span>
                <table class="grid mt6">
                    <tr>
                        <td class="label-float">Costo Total</td><td class="v sm">{{ number_format($orden->costo_total,2) }}</td>
                        <td class="label-float">A cuenta</td><td class="v sm">{{ number_format($orden->adelanto,2) }}</td>
                        <td class="label-float">Saldo</td><td class="v sm">{{ number_format($orden->saldo,2) }}</td>
                    </tr>
                    {{--                <tr>--}}
                    {{--                    <td class="cell" style="width:50%">--}}
                    {{--                        <table class="kv">--}}
                    {{--                            <tr><td class="k sm">Peso fin. (gr)</td><td class="v sm">{{ number_format($orden->peso,3) }}</td></tr>--}}
                    {{--                            --}}{{--                        <tr><td class="k sm">Precio Oro</td><td class="v sm">{{ number_format($precioOro,2) }}</td></tr>--}}
                    {{--                            <tr><td class="k sm">Costo Total</td><td class="v sm">{{ number_format($orden->costo_total,2) }}</td></tr>--}}
                    {{--                        </table>--}}
                    {{--                    </td>--}}
                    {{--                    <td class="cell" style="width:50%">--}}
                    {{--                        <table class="kv">--}}
                    {{--                            <tr><td class="k sm">A cuenta</td><td class="v sm">{{ number_format($orden->adelanto,2) }}</td></tr>--}}
                    {{--                            <tr><td class="k sm">Saldo</td><td class="v sm">{{ number_format($orden->saldo,2) }}</td></tr>--}}
                    {{--                            --}}{{--                        <tr>--}}
                    {{--                            --}}{{--                            <td class="k sm">Estado</td>--}}
                    {{--                            --}}{{--                            <td class="v sm"><span class="chip {{ $chip }}">{{ $estado }}</span></td>--}}
                    {{--                            --}}{{--                        </tr>--}}
                    {{--                        </table>--}}
                    {{--                    </td>--}}
                    {{--                </tr>--}}
                </table>
            </div>
            <!-- Montos + Estado -->
            <!-- Fecha de Entrega -->
            <div class="cell mt6">
                <span class="label-float">Fecha de Entrega:</span>
                <span class="md bold" style="margin-left:4px">{{ $entrega }}</span>
            </div>

            <!-- Nota -->
            <div class="cell mt6">
                <span class="label-float">NOTA</span>
                <div class="xs" style="margin-top:2px">
                    {{ $orden->nota ?: 'Ningun trabajo será entregado sin esta orden. Pasado los 30 dias la casa no se hace responsable' }}
                </div>
            </div>

            <!-- Firmas compactas -->
            <div class="dot"></div>
            <table class="w100" style="border-collapse:collapse">
                <tr>
                    <td class="sign">
                        <div class="sign-line"></div>
                        <div class="xs">Firma Cliente</div>
                    </td>
                    <td class="sign" style="text-align:right">
                        <div class="sign-line"></div>
                        <div class="xs" style="text-align: center">Firma Joyería</div>
                    </td>
                </tr>
            </table>
        </div>
    @endfor
</div>
</body>
</html>
