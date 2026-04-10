<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Movimientos al Inventario</title>
    @include('pdf.partials.report_theme')
</head>
<body>
    <div class="report-shell">
        <div class="report-header">
            <div class="brand">JOYAS ROSARIO</div>
            <div class="report-title">MOVIMIENTOS AL INVENTARIO</div>
        </div>

        <table class="meta-table">
            <tr>
                <td><span class="meta-label">FECHA INICIO</span>{{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'TODOS' }}</td>
                <td><span class="meta-label">FECHA FINAL</span>{{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'TODOS' }}</td>
                <td><span class="meta-label">LINEA</span>{{ $linea ?: 'TODOS' }}</td>
                <td><span class="meta-label">ESTUCHE</span>{{ $estuche ?: 'TODOS' }}</td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
            <tr>
                <th style="width: 12%;">FECHA</th>
                <th style="width: 10%;">CODIGO</th>
                <th style="width: 8%;">FOTO</th>
                <th>DETALLE</th>
                <th style="width: 7%;">PESO</th>
                <th style="width: 10%;">LINEA</th>
                <th style="width: 13%;">ESTADO</th>
                <th style="width: 14%;">USUARIO</th>
            </tr>
            </thead>
            <tbody>
            @forelse($movimientos as $movimiento)
                <tr>
                    <td>{{ $movimiento['fecha'] }}</td>
                    <td>{{ $movimiento['codigo'] }}</td>
                    <td class="image-cell">
                        @if(!empty($movimiento['imagen']) && file_exists(public_path('images/'.$movimiento['imagen'])))
                            <img class="image-box" src="{{ public_path('images/'.$movimiento['imagen']) }}" alt="joya">
                        @elseif(file_exists(public_path('images/joya.png')))
                            <img class="image-box" src="{{ public_path('images/joya.png') }}" alt="joya">
                        @endif
                    </td>
                    <td>{{ $movimiento['detalle'] }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($movimiento['peso'], 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $movimiento['linea'] ?? 'TODOS' }}</td>
                    <td><span class="status-chip">{{ $movimiento['estado'] }}</span></td>
                    <td>{{ $movimiento['usuario'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">No hay movimientos para imprimir.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="note">
            <strong>NOTA ESTADO:</strong> puede ser cualquier cambio realizado al inventario: ingreso, venta, cambio de estuche o eliminacion.
        </div>
    </div>
</body>
</html>
