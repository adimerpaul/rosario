<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Existencias de Inventario</title>
    @include('pdf.partials.report_theme')
</head>
<body>
    <div class="report-shell">
        <div class="report-header">
            <div class="brand">JOYAS ROSARIO</div>
            <div class="report-title">EXISTENCIAS DE INVENTARIO</div>
        </div>

        <table class="meta-table">
            <tr>
                <td><span class="meta-label">LINEA</span>{{ $linea ?: 'TODOS' }}</td>
                <td><span class="meta-label">ESTUCHE</span>{{ $estuche ?: 'TODOS' }}</td>
                <td><span class="meta-label">REGISTROS</span>{{ count($joyas) }}</td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
            <tr>
                <th style="width: 11%;">CODIGO</th>
                <th style="width: 8%;">FOTO</th>
                <th>DETALLE</th>
                <th style="width: 8%;">PESO</th>
                <th style="width: 12%;">LINEA</th>
                <th style="width: 13%;">ESTADO</th>
                <th style="width: 15%;">USUARIO</th>
            </tr>
            </thead>
            <tbody>
            @forelse($joyas as $joya)
                <tr>
                    <td>{{ $joya['codigo'] }}</td>
                    <td class="image-cell">
                        @if(!empty($joya['imagen']) && file_exists(public_path('images/'.$joya['imagen'])))
                            <img class="image-box" src="{{ public_path('images/'.$joya['imagen']) }}" alt="joya">
                        @elseif(file_exists(public_path('images/joya.png')))
                            <img class="image-box" src="{{ public_path('images/joya.png') }}" alt="joya">
                        @endif
                    </td>
                    <td>{{ $joya['detalle'] }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($joya['peso'], 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $joya['linea'] }}</td>
                    <td><span class="status-chip">{{ $joya['estado'] }}</span></td>
                    <td>{{ $joya['usuario'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center">No hay joyas disponibles en inventario.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
