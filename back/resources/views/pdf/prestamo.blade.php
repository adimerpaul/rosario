<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Préstamo</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color:#111; }
        .sheet { border:2px solid #991b1b; border-radius:14px; padding:10px 12px; }
        .brand { color:#991b1b; }
        .title { font-size:17px; font-weight:800; letter-spacing:.2px; }
        .sm { font-size:11px; }
        .md { font-size:12px; }
        .xs { font-size:10px; }
        .bold { font-weight:700; }
        .right { text-align:right; }
        .center { text-align:center; }
        .muted { color:#666; }
        .badge {
            display:inline-block;
            border:1.5px solid #991b1b;
            border-radius:12px;
            padding:4px 10px;
            font-size:11px;
            font-weight:700;
        }
        .box {
            border:1.5px solid #991b1b;
            border-radius:12px;
            padding:8px 10px;
            vertical-align:top;
        }
        .label {
            display:inline-block;
            font-size:10px;
            color:#991b1b;
            font-weight:700;
            margin-bottom:3px;
        }
        table.grid { width:100%; border-collapse:separate; border-spacing:6px; }
        .sign-line { border-top:1px solid #111; width:78%; margin:20px auto 4px; }
    </style>
</head>
<body>
@php
    $clienteNombre = $cliente->name ?? '—';
    $clienteCI = $cliente->ci ?? '—';
    $logo = public_path('images/logo.png');
@endphp

<div class="sheet">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:82px; vertical-align:top;">
                @if (file_exists($logo))
                    <img src="{{ $logo }}" style="width:62px; display:block;">
                @endif
            </td>
            <td class="center">
                <div class="title brand">{{ $empresa['nombre'] ?? 'Joyería Rosario' }}</div>
                <div class="sm">{{ $empresa['direccion'] ?? '' }}</div>
                <div class="sm">{{ ($empresa['ciudad'] ?? '') . ' - ' . ($empresa['pais'] ?? '') }}</div>
                <div class="sm">Cel: {{ $empresa['cel'] ?? '' }}</div>
                <div class="md bold" style="margin-top:6px;">PRÉSTAMO POR 30 DÍAS</div>
            </td>
            <td class="right" style="width:150px; vertical-align:top;">
                <div class="badge">Nro: {{ $numero ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="grid" style="margin-top:8px;">
        <tr>
            <td class="box" style="width:42%;">
                <div class="label">Cliente</div>
                <div class="md bold">{{ $clienteNombre }}</div>
                <div class="sm">CI: {{ $clienteCI }}</div>
                <div class="sm">Cel: {{ $cel ?? '—' }}</div>
            </td>
            <td class="box" style="width:18%;">
                <div class="label">Lugar</div>
                <div class="md">{{ $lugar ?? '—' }}</div>
            </td>
            <td class="box" style="width:20%;">
                <div class="label">Fecha</div>
                <div class="md">{{ $fecha_creacion ? $fecha_creacion->format('d/m/Y') : '—' }}</div>
            </td>
            <td class="box" style="width:20%;">
                <div class="label">Vencimiento</div>
                <div class="md">{{ $vencimiento ? $vencimiento->format('d/m/Y') : '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td class="box" style="width:25%;">
                <div class="label">Moneda</div>
                <div class="md">{{ $monedaLarga }}</div>
            </td>
            <td class="box" style="width:25%;">
                <div class="label">Valor bienes</div>
                <div class="md">{{ number_format($valorBienes, 2) }} {{ $monedaCorta }}</div>
            </td>
            <td class="box" style="width:25%;">
                <div class="label">Capital solicitado</div>
                <div class="md">{{ number_format($capitalSolic, 2) }} {{ $monedaCorta }}</div>
            </td>
            <td class="box" style="width:25%;">
                <div class="label">Cargo mensual</div>
                <div class="md">{{ number_format($cargoMensual, 2) }} {{ $monedaCorta }}</div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td class="box" style="width:33%;">
                <div class="label">Interés</div>
                <div class="md">{{ rtrim(rtrim(number_format($interesMensual,2), '0'),'.') }} %</div>
                <div class="xs muted">30 días: {{ number_format($interesMonto30,2) }} {{ $monedaCorta }}</div>
            </td>
            <td class="box" style="width:33%;">
                <div class="label">Almacén</div>
                <div class="md">{{ rtrim(rtrim(number_format($almacenMensual,2), '0'),'.') }} %</div>
                <div class="xs muted">30 días: {{ number_format($almacenMonto30,2) }} {{ $monedaCorta }}</div>
            </td>
            <td class="box" style="width:34%;">
                <div class="label">Plazo</div>
                <div class="md">{{ $plazoDias }} días</div>
            </td>
        </tr>
    </table>

    <div class="box" style="margin-top:6px;">
        <div class="label">Detalle de joyas</div>
        <div class="md">{{ $detalle ?? '—' }}</div>
    </div>

    <table class="grid" style="margin-top:6px;">
        <tr>
            <td class="box" style="width:33%;">
                <div class="label">Peso total</div>
                <div class="md">{{ number_format($pesoTotalGr, 3) }} gr</div>
            </td>
            <td class="box" style="width:33%;">
                <div class="label">Peso merma/piedras</div>
                <div class="md">{{ number_format($mermaGr, 3) }} gr</div>
            </td>
            <td class="box" style="width:34%;">
                <div class="label">Peso en oro</div>
                <div class="md">{{ number_format($pesoOroGr, 3) }} gr</div>
            </td>
        </tr>
    </table>

    <div class="xs muted" style="margin-top:8px;">
        El cliente declara la veracidad de la información proporcionada y el origen lícito de los bienes dejados en prenda.
        P.B.: peso bruto.
    </div>

    <table style="width:100%; margin-top:18px;">
        <tr>
            <td style="width:50%;">
                <div class="sign-line"></div>
                <div class="xs center">Firma del cliente</div>
            </td>
            <td style="width:50%;">
                <div class="sign-line"></div>
                <div class="xs center">Firma joyería</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
