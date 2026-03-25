<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; }
        .title { text-align: center; font-weight: 700; font-size: 20px; margin-bottom: 18px; }
        .subtitle { font-weight: 700; font-size: 18px; margin: 12px 0 10px; }
        .meta { margin-bottom: 18px; font-size: 13px; }
        .meta-row { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px 8px; vertical-align: top; }
        th { text-align: left; font-weight: 700; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row td { font-weight: 700; }
        .empty { margin-top: 18px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="title">JOYAS ROSARIO</div>
    <div class="subtitle">{{ $titulo }}</div>

    <div class="meta">
        <div class="meta-row">FECHA INICIO: {{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'TODOS' }}</div>
        <div class="meta-row">FECHA FINAL: {{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'TODOS' }}</div>
        <div class="meta-row">LINEA: {{ $linea ?: 'TODOS' }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 16%;">FECHA</th>
            <th style="width: 14%;">CODIGO</th>
            <th>DETALLE</th>
            <th style="width: 10%;">PESO</th>
            <th style="width: 14%;">MONTO</th>
            <th style="width: 14%;">LINEA</th>
            <th style="width: 16%;">USUARIO</th>
        </tr>
        </thead>
        <tbody>
        @forelse($ventas as $venta)
            <tr>
                <td>{{ $venta['fecha'] }}</td>
                <td>{{ $venta['codigo'] }}</td>
                <td>{{ $venta['detalle'] }}</td>
                <td class="center">{{ rtrim(rtrim(number_format($venta['peso'], 2, '.', ''), '0'), '.') }}</td>
                <td class="right">{{ number_format($venta['monto'], 2, '.', '') }}</td>
                <td>{{ $venta['linea'] }}</td>
                <td>{{ $venta['usuario'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="center">No hay ventas para imprimir.</td>
            </tr>
        @endforelse
        @if(count($ventas))
            <tr class="total-row">
                <td colspan="4">TOTAL</td>
                <td class="right">{{ number_format($total, 2, '.', '') }}</td>
                <td></td>
                <td></td>
            </tr>
        @endif
        </tbody>
    </table>
</body>
</html>
