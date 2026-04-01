<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de Trabajo</title>
    <style>
        @page { margin: 9mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 11px; }
        .wrap { width: 100%; }
        .sheet {
            border: 1.8px solid #e56b7d;
            border-radius: 16px;
            padding: 8px 10px 9px;
            margin-bottom: 10px;
        }
        .brand { color: #e56b7d; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .muted { color: #444; }
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .logo { width: 54px; }
        .rings { width: 48px; height: auto; }
        .orden-photo {
            width: 62px;
            height: 62px;
            border: 1px solid #e56b7d;
            border-radius: 8px;
            padding: 2px;
            object-fit: cover;
        }
        .title { font-size: 17px; font-weight: 800; letter-spacing: .3px; }
        .sub { font-size: 10.2px; line-height: 1.18; }
        .pill {
            display: inline-block;
            min-width: 96px;
            border: 1.5px solid #e56b7d;
            border-radius: 12px;
            padding: 5px 8px;
            text-align: center;
            font-size: 10.5px;
            line-height: 1.15;
            margin-bottom: 4px;
        }
        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
        }
        .cell {
            border: 1.4px solid #e56b7d;
            border-radius: 12px;
            padding: 7px 8px 6px;
            position: relative;
            vertical-align: top;
        }
        .label-float {
            position: absolute;
            top: -7px;
            left: 12px;
            background: #fff;
            color: #e56b7d;
            padding: 0 4px;
            font-size: 9px;
            font-weight: 700;
        }
        .value-lg { font-size: 12.5px; }
        .value-xl { font-size: 14px; }
        .money-row td {
            width: 33.33%;
            border: 1.4px solid #e56b7d;
            border-radius: 12px;
            padding: 8px 8px 6px;
            position: relative;
            text-align: center;
            font-weight: 700;
            font-size: 12px;
        }
        .sketch-box {
            min-height: 88px;
        }
        .sketch-guide {
            margin-top: 8px;
            height: 54px;
            border-top: 1px dashed #d9b1b8;
            border-bottom: 1px dashed #d9b1b8;
        }
        .nota {
            min-height: 40px;
            font-size: 10px;
            line-height: 1.2;
        }
        .spacer-top { margin-top: 4px; }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $entrega = $orden->fecha_entrega ? \Carbon\Carbon::parse($orden->fecha_entrega)->format('d/m/Y') : '—';
        $clienteNombre = mb_strtoupper($orden->cliente->name ?? 'N/A');
        $telefono = $orden->cliente->cellphone ?? $orden->celular ?? 'N/A';
        $detalleTrabajo = trim((string) ($orden->detalle ?? ''));
        $kilates = trim((string) ($orden->kilates18 ?? ''));
        $nota = $orden->nota ?: 'Ningun trabajo sera entregado sin la presente orden. Importante: en caso de no recojo se espera un maximo de 90 dias antes de proceder a la fundicion de la joya.';
    @endphp

    @for($i = 0; $i < 2; $i++)
        <div class="sheet">
            <table class="head">
                <tr>
                    <td style="width: 110px;" class="center">
                        <img class="logo" src="{{ public_path('images/logo.png') }}">
                        <div class="muted" style="font-size: 8.8px; line-height: 1.18; margin-top: 3px;">
                            Calidad y garantia<br>
                            Oro 18 Klts<br>
                            Plata 925 decimos
                        </div>
                    </td>
                    <td class="center">
                        <div class="muted" style="font-size: 9px;">{{ $hoy->format('d/m/Y') }}</div>
                        <div class="brand title spacer-top">ORDEN DE TRABAJO</div>
                        <div class="sub">
                            DIR. ADOLFO MIER, POTOSI Y PAGADOR (LADO PALACE HOTEL)<br>
                            CEL. 73800584 TELF. 52-55713<br>
                            ORURO - BOLIVIA
                        </div>
                    </td>
                    <td style="width: 170px;" class="right">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="right" style="padding-right: 6px; width: 70px;">
                                    @if(!empty($orden->foto_modelo) && file_exists(public_path('images/'.$orden->foto_modelo)))
                                        <img class="orden-photo" src="{{ public_path('images/'.$orden->foto_modelo) }}">
                                    @elseif(file_exists(public_path('images/rings.png')))
                                        <img class="rings" src="{{ public_path('images/rings.png') }}">
                                    @endif
                                </td>
                                <td class="right">
                                    <div class="pill"><span class="bold">Nro:</span> {{ $orden->numero }}</div>
                                    <div class="pill"><span class="bold">Bs.</span> {{ number_format($orden->costo_total, 2) }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="grid" style="margin-top: 2px;">
                <tr>
                    <td class="cell" style="width: 58%;" colspan="2">
                        <span class="label-float">El Señor</span>
                        <div class="value-xl center">{{ $clienteNombre }}</div>
                    </td>
{{--                    <td class="cell" style="width: 42%;">--}}
{{--                        <span class="label-float">Telefono</span>--}}
{{--                        <div class="value-xl center">{{ $telefono }}</div>--}}
{{--                    </td>--}}
                </tr>
                <tr>
                    <td class="cell" style="width: 64%;">
                        <span class="label-float">Trabajo de Joya</span>
                        <div class="value-lg" style="line-height: 1.25;">
                            {{ $detalleTrabajo }}
                            @if($orden->peso)
                                / Peso: {{ number_format((float) $orden->peso, 2) }} gr.
                            @endif
                            @if($kilates !== '')
                                / Oro: {{ $kilates }}
                            @endif
                        </div>
                    </td>
                    <td class="cell sketch-box" style="width: 36%;">
                        <span class="label-float">Grafico / Referencia</span>
                        <div class="muted" style="font-size: 8.8px;">Espacio para dibujo o detalle del cliente</div>
                        <div class="sketch-guide"></div>
                    </td>
                </tr>
            </table>

            <table class="grid money-row" style="margin-top: 0;">
                <tr>
                    <td>
                        <span class="label-float">Costo Total</span>
                        {{ number_format($orden->costo_total, 2) }}
                    </td>
                    <td>
                        <span class="label-float">A cuenta</span>
                        {{ number_format($orden->adelanto, 2) }}
                    </td>
                    <td>
                        <span class="label-float">Saldo</span>
                        {{ number_format($orden->saldo, 2) }}
                    </td>
                </tr>
            </table>

            <table class="grid" style="margin-top: 0;">
                <tr>
                    <td class="cell" style="width: 40%;">
                        <span class="label-float">Fecha de Entrega</span>
                        <div class="value-xl center">{{ $entrega }}</div>
                    </td>
                    <td class="cell" style="width: 60%;">
                        <span class="label-float">Observaciones</span>
                        <div style="height: 24px;"></div>
                    </td>
                </tr>
            </table>

            <div class="cell nota" style="margin: 4px;">
                <span class="label-float">NOTA</span>
                <div>{{ $nota }}</div>
            </div>
        </div>

        @if($i === 0)
            <div style="height: 6px;"></div>
        @endif
    @endfor
</div>
</body>
</html>
