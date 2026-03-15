<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de pago</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; }
        .sheet {
            border: 2px solid #991b1b;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .brand { color: #991b1b; }
        .sm { font-size: 11px; }
        .xs { font-size: 10px; }
        .md { font-size: 12px; }
        .bold { font-weight: 700; }
        .title {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .2px;
        }
        .badge {
            display: inline-block;
            border: 1.5px solid #991b1b;
            border-radius: 12px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
        }
        .ticket {
            border: 1.5px solid #6aa84f;
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 8px;
        }
        .section-title {
            font-size: 11px;
            color: #991b1b;
            font-weight: 700;
            letter-spacing: .3px;
            margin-bottom: 8px;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .label {
            width: 42%;
            font-size: 12px;
            font-weight: 700;
            color: #444;
        }
        .value {
            font-size: 13px;
            font-weight: 700;
            color: #111;
        }
        .divider {
            border-top: 1px dashed #d9d9d9;
            margin: 8px 0;
        }
        .muted { color: #666; }
    </style>
</head>
<body>
@php
    $clienteNombre = mb_strtoupper($cliente->name ?? '—');
    $peso = number_format((float) ($prestamo->peso ?? 0), 0);
    $monto = number_format((float) ($pago->monto ?? 0), 0, '.', '');
    $fechaCancelado = $prestamo->fecha_limite
        ? \Illuminate\Support\Carbon::parse($prestamo->fecha_limite)->format('j/m/Y')
        : '—';
    $fechaVencimiento = $prestamo->fecha_cancelacion
        ? \Illuminate\Support\Carbon::parse($prestamo->fecha_cancelacion)->format('j/m/Y')
        : '—';
    $logo = public_path('images/logo.png');
@endphp

<div class="sheet">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 72px; vertical-align: top;">
                @if (file_exists($logo))
                    <img src="{{ $logo }}" style="width: 50px; display: block;">
                @endif
            </td>
            <td class="center">
                <div class="title brand">{{ $empresa['nombre'] ?? 'Joyería Rosario' }}</div>
                <div class="sm">{{ $empresa['direccion'] ?? '' }}</div>
                <div class="sm">{{ ($empresa['ciudad'] ?? '') . ' - ' . ($empresa['pais'] ?? '') }}</div>
                <div class="sm">Cel: {{ $empresa['cel'] ?? '' }}</div>
                <div class="md bold" style="margin-top: 4px;">COMPROBANTE DE PAGO</div>
            </td>
            <td class="right" style="width: 120px; vertical-align: top;">
                <div class="badge">Pago #{{ $pago->id }}</div>
            </td>
        </tr>
    </table>

    <div class="ticket">
        <div class="section-title center">DETALLE DEL PAGO</div>
        <table class="detail-table">
            <tr>
                <td class="label">Nombre:</td>
                <td class="value">{{ $clienteNombre }}</td>
            </tr>
            <tr>
                <td class="label">Peso:</td>
                <td class="value">{{ $peso }} GR</td>
            </tr>
            <tr>
                <td class="label">Monto:</td>
                <td class="value">{{ $monto }} Bs</td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="detail-table">
            <tr>
                <td class="label">Mes cancelado:</td>
                <td class="value">{{ $fechaCancelado }}</td>
            </tr>
            <tr>
                <td class="label">Mes vencimiento:</td>
                <td class="value">{{ $fechaVencimiento }}</td>
            </tr>
        </table>
    </div>

    <div class="xs muted center" style="margin-top: 8px;">
        Comprobante emitido el {{ $hoy->format('d/m/Y H:i') }}@if($usuario?->name) por {{ $usuario->name }}@endif
    </div>
</div>
</body>
</html>
