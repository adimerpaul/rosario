<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Movimientos al Inventario</title>
    <style>
        @page { margin: 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; }
        .title { text-align: center; font-weight: 700; font-size: 20px; margin-bottom: 16px; }
        .subtitle { font-weight: 700; font-size: 18px; margin: 10px 0; }
        .meta { margin-bottom: 16px; font-size: 13px; }
        .meta-row { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px 8px; vertical-align: top; }
        th { text-align: left; font-weight: 700; }
        .note { margin-top: 28px; font-size: 12px; }
        .note strong { text-decoration: underline; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="title">JOYAS ROSARIO</div>
    <div class="subtitle">MOVIMIENTOS AL INVENTARIO</div>

    <div class="meta">
        <div class="meta-row">FECHA INICIO: {{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'TODOS' }}</div>
        <div class="meta-row">FECHA FINAL: {{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'TODOS' }}</div>
        <div class="meta-row">LINEA: {{ $linea ?: 'TODOS' }}</div>
        <div class="meta-row">ESTUCHE: {{ $estuche ?: 'TODOS' }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 16%;">FECHA</th>
            <th style="width: 14%;">CODIGO</th>
            <th>DETALLE</th>
            <th style="width: 10%;">PESO</th>
            <th style="width: 14%;">LINEA</th>
            <th style="width: 18%;">ESTADO</th>
            <th style="width: 16%;">USUARIO</th>
        </tr>
        </thead>
        <tbody>
        @forelse($movimientos as $movimiento)
            <tr>
                <td>{{ $movimiento['fecha'] }}</td>
                <td>{{ $movimiento['codigo'] }}</td>
                <td>{{ $movimiento['detalle'] }}</td>
                <td class="center">{{ rtrim(rtrim(number_format($movimiento['peso'], 2, '.', ''), '0'), '.') }}</td>
                <td>{{ $movimiento['linea'] ?? 'TODOS' }}</td>
                <td>{{ $movimiento['estado'] }}</td>
                <td>{{ $movimiento['usuario'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="center">No hay movimientos para imprimir.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="note">
        <strong>NOTA ESTADO</strong>, puede ser cualquier cambio realizado al inventario: ingreso a inventario, vendido, cambio de estuche o eliminación de inventario.
    </div>
</body>
</html>
