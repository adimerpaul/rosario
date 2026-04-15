<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Existencias de Inventario</title>
    @include('pdf.partials.report_theme')
</head>
<body>
    @php
        $joyasEnVitrina = collect($joyas)->filter(fn ($joya) => ($joya['estado'] ?? null) === 'EN VITRINA')->values();
        $joyasReservadas = collect($joyas)->filter(fn ($joya) => ($joya['estado'] ?? null) === 'RESERVADO')->values();
    @endphp

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

        @php
            $secciones = [
                'JOYAS EN VITRINA' => $joyasEnVitrina,
                'JOYAS RESERVADAS' => $joyasReservadas,
            ];
        @endphp

        @foreach($secciones as $tituloSeccion => $items)
            <div class="section-title">
                {{ $tituloSeccion }} ({{ $items->count() }})
            </div>

            <table class="report-table section-table">
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
                @forelse($items as $joya)
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
                        <td colspan="7" class="center">No hay joyas en esta seccion.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        @endforeach
    </div>

    <style>
        .section-title {
            margin: 10px 0 4px;
            padding: 4px 6px;
            border: 1px solid #b8c3cc;
            background: #f7f9fb;
            font-size: 9px;
            font-weight: 700;
            color: #314556;
            letter-spacing: 0.25px;
        }
        .section-table {
            margin-bottom: 8px;
        }
    </style>
</body>
</html>
