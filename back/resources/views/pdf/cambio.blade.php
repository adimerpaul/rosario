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
        <td class="box" >
            <div class="label">Agencia</div>
            <div>{!! nl2br(e($agencia)) !!}</div>
        </td>
        <td class="box" style="width:35%">
            <div class="label">Fecha </div>
            <div>{{ $fechaAt->translatedFormat('d \\de F \\de Y') }}</div>
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
{{--        <td class="box">--}}
{{--            <div class="label">Fecha</div>--}}
{{--            <div>{{ $fechaAt->translatedFormat('d \\de F \\de Y - H:i:s') }}</div>--}}
{{--        </td>--}}
        <td class="box" colspan="2">
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
{{--    <tr>--}}
{{--        <td class="box" style="width:50%">--}}
{{--            <div class="label">Importe Recibido</div>--}}
{{--            <div class="strong">{{ number_format($montoBs, 2) }} Bs</div>--}}
{{--        </td>--}}
{{--        <td class="box" style="width:50%">--}}
{{--            <div class="label">Importe Entregado</div>--}}
{{--            <div class="strong">{{ number_format($montoUsd, 2) }} $us</div>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--    Peso total	Merma	Peso en Oro--}}
{{--    Monto prestado Dolares	Igual al moto sacado dibidio entre 6.96--}}
{{--    Monto Bolivianos 		1000Bs--}}
    <tr>
        <td class="box" >
{{--            {--}}
{{--            prestamo: {--}}
{{--            id: 8,--}}
{{--            numero: "PR-000008-2026",--}}
{{--            fecha_creacion: "2026-01-05",--}}
{{--            fecha_limite: "2026-01-05",--}}
{{--            fecha_cancelacion: "2026-02-05",--}}
{{--            cliente_id: 3,--}}
{{--            user_id: 1,--}}
{{--            merma: "0.000",--}}
{{--            peso_neto: "3.000",--}}
{{--            peso: "3.000",--}}
{{--            precio_oro: "900.00",--}}
{{--            valor_total: "2700.00",--}}
{{--            valor_prestado: "3000.00",--}}
{{--            interes: "3.00",--}}
{{--            almacen: "3.00",--}}
{{--            saldo: 3000,--}}
{{--            celular: "60495286",--}}
{{--            detalle: " [TC: 6.96]",--}}
{{--            estado: "Activo",--}}
{{--            dias_transcurridos: 1,--}}
{{--            cargo_diario: 6,--}}
{{--            cargos_acumulados: 6,--}}
{{--            total_deuda: 3000,--}}
{{--            deuda_interes: 0,--}}
{{--            user: {--}}
{{--            id: 1,--}}
{{--            name: "Roger arias",--}}
{{--            username: "admin",--}}
{{--            role: "Administrador",--}}
{{--            avatar: "default.png",--}}
{{--            email: null,--}}
{{--            email_verified_at: null--}}
{{--            },--}}
{{--            cliente: {--}}
{{--            id: 3,--}}
{{--            name: "ROBERTO HINOJOSA ORGAZ",--}}
{{--            ci: "3089963",--}}
{{--            celular: null,--}}
{{--            status: "Confiable",--}}
{{--            observation: null,--}}
{{--            cellphone: "60495286",--}}
{{--            address: null--}}
{{--            }--}}
{{--            },--}}
{{--            agencia: "JOYERÍA ROSARIO",--}}
{{--            direccion: "Adolfo Mier entre Potosi y pagador (Lado palace Hotel)",--}}
{{--            usuario: "ADMIN",--}}
{{--            cuint: "—",--}}
{{--            cliente: "ROBERTO HINOJOSA ORGAZ",--}}
{{--            fechaAt: "2026-01-05T04:00:00.000000Z",--}}
{{--            montoBs: 3000,--}}
{{--            montoUsd: 431.03,--}}
{{--            son: "CUATROCIENTOS TREINTA Y UNO 03/100 DÓLARES",--}}
{{--            concepto: "Cambio Dólares (asociado a préstamo PR-000008-2026)",--}}
{{--            docSerie: "PR",--}}
{{--            docNro: "00000008",--}}
{{--            refPrestamo: "PR-000008-2026",--}}
{{--            tipoCambio: "6.96"--}}
{{--            }--}}
            <div class="label">Peso Total (g)</div>
            <div class="strong">{{ number_format($prestamo->peso, 2) }} g</div>
        </td>
        <td class="box" >
            <div class="label">Merma (g)</div>
            <div class="strong">{{ number_format($prestamo->merma, 2) }} g</div>
        </td>
        <td class="box" >
            <div class="label">Peso en Oro (g)</div>
            <div class="strong">{{ number_format($prestamo->peso_neto, 2) }} g</div>
        </td>
    </tr>
    <tr>
        <td class="box" >
            <div class="label">Monto Prestado ($us)</div>
{{--            tipoCambio--}}
            <div class="strong">{{ number_format($prestamo->valor_prestado/$tipoCambio, 2) }} $us</div>
        </td>
        <td class="box" colspan="2">
            <div class="label">Monto Bolivianos (Bs)</div>
            <div class="strong">{{ number_format($montoBs, 2) }} Bs</div>
        </td>
    </tr>
    <tr>
        <td class="box">
            <div class="label">Interes</div>
            {{--            <div class="text-weight-medium">{{ p.total_deuda * (parseFloat(p.interes || 0) + parseFloat(p.almacen || 0)) / 100 | money }}</div>--}}
            <div class="strong">{{ number_format(($prestamo->total_deuda * (floatval($prestamo->interes) + floatval($prestamo->almacen)) / 100)/$tipoCambio, 2) }} $us </div>
        </td>
        <td class="box" colspan="2">
            <div class="label">Interes</div>
{{--            <div class="text-weight-medium">{{ p.total_deuda * (parseFloat(p.interes || 0) + parseFloat(p.almacen || 0)) / 100 | money }}</div>--}}
            <div class="strong">{{ number_format($prestamo->total_deuda * (floatval($prestamo->interes) + floatval($prestamo->almacen)) / 100, 2) }} Bs</div>
        </td>
    </tr>

</table>

{{--<div class="box">--}}
{{--    <div class="label">Son</div>--}}
{{--    <div class="strong">{{ $son }}</div>--}}
{{--</div>--}}

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
        <td style="text-align:center; width:50%;" colspan="2">
            <br>
            <br>
            <br>
            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>
            <div class="muted">Firma del Cliente</div>
        </td>
{{--        <td style="text-align:center; width:50%;">--}}
{{--            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>--}}
{{--            <div class="muted">Firma Caja/Agencia</div>--}}
{{--        </td>--}}
    </tr>
</table>
</body>
</html>
