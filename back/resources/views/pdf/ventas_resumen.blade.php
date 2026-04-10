<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    @include('pdf.partials.report_theme')
</head>
<body>
    <div class="report-shell">
        <div class="report-header">
            <div class="brand">JOYAS ROSARIO</div>
            <div class="report-title">{{ $titulo }}</div>
        </div>

        <table class="meta-table">
            <tr>
                <td><span class="meta-label">FECHA INICIO</span>{{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'TODOS' }}</td>
                <td><span class="meta-label">FECHA FINAL</span>{{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'TODOS' }}</td>
                <td><span class="meta-label">LINEA</span>{{ $linea ?: 'TODOS' }}</td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
            <tr>
                <th style="width: 13%;">FECHA</th>
                <th style="width: 11%;">CODIGO</th>
                <th>DETALLE</th>
                <th style="width: 8%;">PESO</th>
                <th style="width: 11%;">MONTO</th>
                <th style="width: 11%;">LINEA</th>
                <th style="width: 14%;">USUARIO</th>
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
    </div>
</body>
</html>
