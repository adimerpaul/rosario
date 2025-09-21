<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Cambio de Moneda</title>
    <style>
        @page { margin: 10mm; }
        body{ font-family: DejaVu Sans, sans-serif; color:#111; font-size:12px; }
        .title{ font-weight:800; font-size:16px; text-align:center; margin-bottom:8px; }
        .muted{ color:#666; }
        .grid{ width:100%; border-collapse:separate; border-spacing:6px; }
        .box{ border:1px solid #333; border-radius:6px; padding:6px 8px; }
        .label{ font-size:11px; color:#444; margin-bottom:2px; }
        .row{ width:100%; }
        .right{ text-align:right; }
        .sep{ height:6px; }
        .strong{ font-weight:700; }
        .mono{ font-family: "DejaVu Sans Mono", monospace; }
    </style>
</head>
<body>
<div class="title">COMPROBANTE DE CAMBIO DE MONEDA</div>

<table class="grid">
    <tr>
        <td class="box" style="width:65%">
            <div class="label">Agencia</div>
            <div>{!! nl2br(e($agencia)) !!}</div>
        </td>
        <td class="box" style="width:35%">
            <div class="label">C.U.I.NT</div>
            <div>{{ $cuint }}</div>
        </td>
    </tr>
    <tr>
        <td class="box">
            <div class="label">Dirección</div>
            <div>{{ $direccion }}</div>
        </td>
        <td class="box">
            <div class="label">Usuario</div>
            <div class="strong">{{ $usuario }}</div>
        </td>
    </tr>
    <tr>
        <td class="box">
            <div class="label">Fecha</div>
            <div>{{ $fechaAt->translatedFormat('d \\de F \\de Y - H:i:s') }}</div>
        </td>
        <td class="box">
            <div class="label">Cliente</div>
            <div class="strong">{{ $cliente }}</div>
        </td>
    </tr>
</table>

<div class="sep"></div>

<div class="box">
    <div class="label">TIPO CAMBIO OFICIAL</div>
    <div class="muted">Tipo de cambio aplicado: <span class="mono">{{ number_format($tipoCambio, 2) }} Bs/$us</span></div>
</div>

<table class="grid">
    <tr>
        <td class="box" style="width:50%">
            <div class="label">Importe Recibido</div>
            <div class="strong">{{ number_format($montoBs, 2) }} Bs</div>
        </td>
        <td class="box" style="width:50%">
            <div class="label">Importe Entregado</div>
            <div class="strong">{{ number_format($montoUsd, 2) }} $us</div>
        </td>
    </tr>
</table>

<div class="box">
    <div class="label">Son</div>
    <div class="strong">{{ $son }}</div>
</div>

<table class="grid">
    <tr>
        <td class="box" style="width:60%">
            <div class="label">Concepto</div>
            <div>Cambio Dólares</div>
            <div class="muted">{{ $concepto }}</div>
        </td>
        <td class="box" style="width:40%">
            <div class="label">Doc. Nro.</div>
            <div class="mono">{{ $docSerie }} &nbsp;&nbsp; {{ $docNro }}</div>
        </td>
    </tr>
</table>

<div class="box">
    <div class="label">Referencia de Préstamo</div>
    <div class="mono strong">{{ $refPrestamo }}</div>
</div>

<table style="width:100%; margin-top:24px;">
    <tr>
        <td style="text-align:center; width:50%;">
            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>
            <div class="muted">Firma del Cliente</div>
        </td>
        <td style="text-align:center; width:50%;">
            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>
            <div class="muted">Firma Caja/Agencia</div>
        </td>
    </tr>
</table>
</body>
</html>
