<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato de préstamo</title>
    <style>
        @page { margin: 8mm; }
        body{ font-family: DejaVu Sans, sans-serif; color:#111; }
        .brand{ color:#d00; }
        .xs{font-size:10px} .sm{font-size:11px} .md{font-size:12px} .lg{font-size:14px}
        .bold{font-weight:700} .center{text-align:center}
        .box{ border:1.5px solid #333; border-radius:6px; padding:6px 8px; }
        .grid{ width:100%; border-collapse:separate; border-spacing:8px; }
        .label{ font-size:11px; color:#444; margin-bottom:2px; }
        .kv{ width:100%; }
        .kv td{ padding:2px 0; vertical-align:top; }
        .right{ text-align:right; }
        .title{ font-size:16px; font-weight:800; letter-spacing:.2px; }
        .muted{ color:#666; }
        .mt4{ margin-top:4px } .mt8{ margin-top:8px } .mt12{ margin-top:12px }
        .pill{ display:inline-block; padding:2px 8px; border:1px solid #555; border-radius:10px; font-size:11px; }
    </style>
</head>
<body>
@php
    $clienteNombre = $cliente->name ?? '—';
    $clienteCI     = $cliente->ci   ?? '—';
@endphp

    <!-- ENCABEZADO -->
<table style="width:100%; border-collapse:collapse">
    <tr>
        <td style="width:80px; vertical-align:top">
            @php $logo = public_path('images/logo.png'); @endphp
            @if (file_exists($logo))
                <img src="{{ $logo }}" style="width:70px; display:block; margin:0; padding:0;">
            @endif
        </td>
        <td class="center">
            <div class="title brand">{{ $empresa['nombre'] ?? 'Joyería' }}</div>
            <div class="xs">{{ $empresa['direccion'] ?? '' }}</div>
            <div class="xs">{{ ($empresa['ciudad'] ?? '') . ' - ' . ($empresa['pais'] ?? '') }}</div>
            <div class="xs">Cel: {{ $empresa['cel'] ?? '' }}</div>
            <div class="md bold mt8">CONTRATO DE PRÉSTAMO POR 30 DÍAS</div>
        </td>
        <td class="right" style="width:160px; vertical-align:top">
            <div class="pill">Nro: {{ $numero ?? '—' }}</div>
        </td>
    </tr>
</table>

<!-- FILA 1,2,3,4 -->
<table class="grid mt12">
    <tr>
        <td class="box" style="width:48%">
            <div class="label">1. Lugar y fecha</div>
            <div class="md">{{ $lugar ?? '—' }}, {{ $fecha_creacion? $fecha_creacion->translatedFormat('d \\de F \\de Y') : '—' }}</div>
        </td>
        <td class="box" style="width:22%">
            <div class="label">2. Plazo</div>
            <div class="md">{{ $plazoDias }} Días</div>
        </td>
        <td class="box" style="width:30%">
            <div class="label">3. Vencimiento</div>
            <div class="md">{{ $vencimiento? $vencimiento->format('d/m/Y') : '—' }}</div>
        </td>
    </tr>
    <tr>
        <td class="box" style="width:48%">
            <div class="label">5. Nombre</div>
            <div class="md">{{ $clienteNombre }}</div>
        </td>
        <td class="box" style="width:22%">
            <div class="label">6. C.I.</div>
            <div class="md">{{ $clienteCI }}</div>
        </td>
        <td class="box" style="width:30%">
            <div class="label">4. Moneda</div>
            <div class="md">{{ $monedaLarga }}</div>
        </td>
    </tr>
    <tr>
        <td class="box" style="width:48%">
            <div class="label">7. Cel.</div>
            <div class="md">{{ $cel ?? '—' }}</div>
        </td>
        <td class="box" style="width:22%">
            <div class="label">8. Valor acordado de los bienes</div>
            <div class="md">{{ number_format($valorBienes, 2) }} {{ $monedaCorta }}</div>
        </td>
        <td class="box" style="width:30%">
            <div class="label">9. Capital solicitado</div>
            <div class="md">{{ number_format($capitalSolic, 2) }} {{ $monedaCorta }}</div>
        </td>
    </tr>
</table>

<!-- INTERÉS / GASTOS / CARGO MENSUAL -->
<table class="grid mt8">
    <tr>
        <td class="box" style="width:33%">
            <div class="label">10. Interés</div>
            <div class="md">{{ rtrim(rtrim(number_format($interesMensual,2), '0'),'.') }} %</div>
            <div class="xs muted">Interés 30d: {{ number_format($interesMonto30,2) }} {{ $monedaCorta }}</div>
        </td>
        <td class="box" style="width:33%">
            <div class="label">11. Gastos deuda y conservación</div>
            <div class="md">{{ rtrim(rtrim(number_format($almacenMensual,2), '0'),'.') }} %</div>
            <div class="xs muted">Conservación 30d: {{ number_format($almacenMonto30,2) }} {{ $monedaCorta }}</div>
        </td>
        <td class="box" style="width:34%">
            <div class="label">12. Cargo mensual</div>
            <div class="md">{{ number_format($cargoMensual,2) }} {{ $monedaCorta }}</div>
        </td>
    </tr>
</table>

<!-- DETALLE DE JOYAS + PESOS -->
<div class="box mt12">
    <div class="label">DETALLE DE JOYAS</div>
    <div class="md">{{ $detalle ?? '—' }}</div>
</div>

<table class="grid mt8">
    <tr>
        <td class="box" style="width:33%">
            <div class="label">13. peso Total</div>
            <div class="md">{{ number_format($pesoTotalGr, 3) }} gr</div>
        </td>
        <td class="box" style="width:33%">
            <div class="label">14. peso merma/piedras</div>
            <div class="md">{{ number_format($mermaGr, 3) }} gr</div>
        </td>
        <td class="box" style="width:34%">
            <div class="label">15. peso en oro</div>
            <div class="md">{{ number_format($pesoOroGr, 3) }} gr</div>
        </td>
    </tr>
</table>

<div class="xs muted mt8">
    * El CLIENTE que suscribe el presente documento, declara la veracidad de la información proporcionada y el origen lícito de los bienes dejados en prenda, asimismo se compromete a facilitar la información y documentación adicional del origen y/o destino del dinero solicitado en préstamo, cuando la EMPRESA se lo requiera, además reconoce que cualquier información falsa, dará lugar a las acciones legales que correspondan.
    <br>
    ACLARACIÓN: P. B.: PESO BRUTO
</div>

<table style="width:100%; margin-top:28px;">
    <tr>
        <td class="center" style="width:50%">
            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>
            <div class="xs">Firma del Cliente</div>
        </td>
        <td class="center" style="width:50%">
            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>
            <div class="xs">Firma Joyería</div>
        </td>
    </tr>
</table>
</body>
</html>
