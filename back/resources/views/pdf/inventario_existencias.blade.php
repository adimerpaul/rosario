<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Existencias de Inventario</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 11px; }
        .title { text-align: center; font-weight: 700; font-size: 20px; margin-bottom: 16px; }
        .subtitle { font-weight: 700; font-size: 18px; margin: 10px 0; }
        .meta { margin-bottom: 16px; font-size: 13px; }
        .meta-row { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px; vertical-align: middle; }
        th { text-align: left; font-weight: 700; }
        .image-cell { text-align: center; }
        .image-box {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border: 1px solid #777;
        }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="title">JOYAS ROSARIO</div>
    <div class="subtitle">EXISTENCIAS DE INVENTARIO</div>

    <div class="meta">
        <div class="meta-row">LINEA: {{ $linea ?: 'TODOS' }}</div>
        <div class="meta-row">ESTUCHE: {{ $estuche ?: 'TODOS' }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 14%;">CODIGO</th>
            <th style="width: 16%;">IMAGEN</th>
            <th>DETALLE</th>
            <th style="width: 10%;">PESO</th>
            <th style="width: 16%;">LINEA</th>
            <th style="width: 18%;">USUARIO</th>
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
                <td>{{ $joya['usuario'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center">No hay joyas disponibles en inventario.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
