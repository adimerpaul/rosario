import moment from 'moment'
import Printd from 'printd'

const DIRECT_PRINT_STYLES = `
  @page { size: letter portrait; margin: 10mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; }
  .sheet { border: 2px solid #991b1b; border-radius: 14px; padding: 10px 12px; }
  .box { border: 1.4px solid #991b1b; border-radius: 12px; padding: 8px 10px; }
  .grid { width: 100%; border-collapse: separate; border-spacing: 6px; }
  .center { text-align: center; }
  .right { text-align: right; }
  .title { font-size: 17px; font-weight: 800; letter-spacing: .2px; }
  .sm { font-size: 11px; }
  .xs { font-size: 10px; }
  .md { font-size: 12px; }
  .label { display: inline-block; font-size: 10px; color: #991b1b; font-weight: 700; margin-bottom: 3px; }
  .muted { color: #666; }
  .badge {
    display: inline-block;
    border: 1.5px solid #991b1b;
    border-radius: 12px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
  }
  .sign-line { border-top: 1px solid #111; width: 78%; margin: 20px auto 4px; }
  .sep { height: 6px; }
  .mono { font-family: Consolas, "Courier New", monospace; }
`

function escapeHtml (value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function money (value) {
  return Number(value || 0).toFixed(2)
}

async function fetchPrestamo (axiosInstance, prestamoId) {
  const { data } = await axiosInstance.get(`prestamos/${prestamoId}`)
  return data
}

async function fetchTipoCambio (axiosInstance) {
  try {
    const { data } = await axiosInstance.get('cogs/4')
    return Number(data?.value || data?.valor || data || 6.96) || 6.96
  } catch {
    return 6.96
  }
}

function printHtml (html) {
  const root = document.createElement('div')
  root.innerHTML = html
  const printer = new Printd()

  return new Promise((resolve) => {
    printer.print(root, [DIRECT_PRINT_STYLES], [], ({ launchPrint }) => {
      setTimeout(() => {
        launchPrint()
        resolve()
      }, 80)
    })
  })
}

function buildPrestamoModel (prestamo, tipoCambio) {
  const capital = Number(prestamo?.valor_prestado || 0)
  const interes = Number(prestamo?.interes || 0)
  const almacen = Number(prestamo?.almacen || 0)
  const cargoMensual = capital * (interes + almacen) / 100
  const valorBienes = Number(prestamo?.valor_total || 0)
  const pesoTotal = Number(prestamo?.peso || 0)
  const merma = Number(prestamo?.merma || 0)
  const pesoOro = Number(prestamo?.peso_neto || (pesoTotal - merma))
  const vencimientoBase = prestamo?.fecha_limite ? moment(prestamo.fecha_limite) : moment(prestamo?.fecha_creacion || new Date())

  return {
    numero: prestamo?.numero || '—',
    cliente: prestamo?.cliente?.name || '—',
    ci: prestamo?.cliente?.ci || '—',
    celular: prestamo?.celular || prestamo?.cliente?.cellphone || '—',
    lugar: 'Oruro',
    fechaCreacion: prestamo?.fecha_creacion ? moment(prestamo.fecha_creacion).format('DD/MM/YYYY') : '—',
    fechaCambio: prestamo?.fecha_creacion ? moment(prestamo.fecha_creacion).format('D [de] MMMM [de] YYYY') : moment().format('D [de] MMMM [de] YYYY'),
    vencimiento: vencimientoBase.add(1, 'month').format('DD/MM/YYYY'),
    monedaLarga: 'Dólares',
    monedaCorta: 'SUS',
    valorBienesUsd: money(valorBienes / tipoCambio),
    capitalUsd: money(capital / tipoCambio),
    cargoMensualUsd: money(cargoMensual / tipoCambio),
    cargoMensualBs: money(cargoMensual),
    interesMensual: interes.toFixed(2).replace(/\.00$/, ''),
    almacenMensual: almacen.toFixed(2).replace(/\.00$/, ''),
    interesMonto30: money(capital * interes / 100),
    almacenMonto30: money(capital * almacen / 100),
    plazoDias: 30,
    detalle: prestamo?.detalle || '—',
    pesoTotal: money(pesoTotal),
    merma: money(merma),
    pesoOro: money(pesoOro),
    agencia: 'JOYERIA ROSARIO',
    direccion: 'Adolfo Mier entre Potosi y pagador (Lado palace Hotel)',
    usuario: String(prestamo?.user?.username || prestamo?.user?.name || 'USUARIO').toUpperCase(),
    tipoCambio: money(tipoCambio),
    montoPrestadoBs: money(capital),
    montoPrestadoUsd: money(capital / tipoCambio),
    referencia: prestamo?.numero || `PR-${prestamo?.id || ''}`,
    docSerie: 'PR',
    docNro: String(prestamo?.id || '').padStart(8, '0')
  }
}

function renderPrestamoContrato (model) {
  return `
    <div class="" style="padding-top:55px;">
      <table style="width:100%; border-collapse:collapse;">
        <tr>
          <td style="width:82px; vertical-align:top;"></td>
          <td class="center">
            <br><br>
            <div class="sm">Oruro - Bolivia</div>
            <div class="sm">Cel: 73800584</div>
            <div class="md" style="margin-top:6px; font-weight:700;">PRESTAMO POR 30 DIAS</div>
          </td>
          <td class="right" style="width:150px; vertical-align:top;">
            <br><br>
            <div class="badge">Nro: ${escapeHtml(model.numero)}</div>
          </td>
        </tr>
      </table>

      <table class="grid" style="margin-top:8px;">
        <tr>
          <td class="box" style="width:42%;">
            <div class="label">Cliente</div>
            <div class="md" style="font-weight:700;">${escapeHtml(model.cliente)}</div>
            <div class="sm">CI: ${escapeHtml(model.ci)}</div>
            <div class="sm">Cel: ${escapeHtml(model.celular)}</div>
          </td>
          <td class="box" style="width:18%;">
            <div class="label">Lugar</div>
            <div class="md">${escapeHtml(model.lugar)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Fecha</div>
            <div class="md">${escapeHtml(model.fechaCreacion)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Vencimiento</div>
            <div class="md">${escapeHtml(model.vencimiento)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:25%;">
            <div class="label">Moneda</div>
            <div class="md">${escapeHtml(model.monedaLarga)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Valor bienes</div>
            <div class="md">${escapeHtml(model.valorBienesUsd)} ${escapeHtml(model.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Capital solicitado</div>
            <div class="md">${escapeHtml(model.capitalUsd)} ${escapeHtml(model.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Cargo mensual</div>
            <div class="md">${escapeHtml(model.cargoMensualUsd)} ${escapeHtml(model.monedaCorta)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Interes</div>
            <div class="md">${escapeHtml(model.interesMensual)} %</div>
<!--            <div class="xs muted">30 dias: ${escapeHtml(model.interesMonto30)} ${escapeHtml(model.monedaCorta)}</div>-->
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Almacen</div>
            <div class="md">${escapeHtml(model.almacenMensual)} %</div>
<!--            <div class="xs muted">30 dias: ${escapeHtml(model.almacenMonto30)} ${escapeHtml(model.monedaCorta)}</div>-->
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Plazo</div>
            <div class="md">${escapeHtml(model.plazoDias)} dias</div>
          </td>
        </tr>
      </table>

      <div class="box" style="margin-top:6px;">
        <div class="label">Detalle de joyas</div>
        <div class="md">${escapeHtml(model.detalle)}</div>
      </div>

      <table class="grid" style="margin-top:6px;">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Peso total</div>
            <div class="md">${escapeHtml(model.pesoTotal)} gr</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Peso merma/piedras</div>
            <div class="md">${escapeHtml(model.merma)} gr</div>
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Peso en oro</div>
            <div class="md">${escapeHtml(model.pesoOro)} gr</div>
          </td>
        </tr>
      </table>

      <div class="xs muted" style="margin-top:8px;">
        El cliente declara la veracidad de la informacion proporcionada y el origen licito de los bienes dejados en prenda.
        P.B.: peso bruto.
      </div>

      <table style="width:100%; margin-top:45px;">
        <tr>
          <td style="width:50%;">
            <div class="sign-line"></div>
            <div class="xs center">Firma del cliente</div>
            <div class="xs " style="padding-left: 250px;">
            Nombre: <br>
            CI: <br>
</div>
          </td>
<!--          <td style="width:50%;">-->
<!--            <div class="sign-line"></div>-->
<!--            <div class="xs center">Firma joyeria</div>-->
<!--          </td>-->
        </tr>
      </table>
    </div>
  `
}

function renderCambioMoneda (model) {
  return `
    <div class="sheet" style="border-color:#333;">
      <div class="title center" style="margin-bottom:8px;">COMPROBANTE DE CAMBIO DE MONEDA</div>
      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Agencia</div>
            <div>${escapeHtml(model.agencia)}</div>
          </td>
          <td class="box" style="width:35%">
            <div class="label">Fecha</div>
            <div>${escapeHtml(model.fechaCambio)}</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Direccion</div>
            <div>${escapeHtml(model.direccion)}</div>
          </td>
          <td class="box">
            <div class="label">Usuario</div>
            <div style="font-weight:700;">${escapeHtml(model.usuario)}</div>
          </td>
        </tr>
        <tr>
          <td class="box" colspan="2">
            <div class="label">Cliente</div>
            <div style="font-weight:700;">${escapeHtml(model.cliente)}</div>
          </td>
        </tr>
      </table>

      <div class="sep"></div>

      <div class="box">
        <div class="label">TIPO CAMBIO OFICIAL</div>
        <div class="muted">Tipo de cambio aplicado: <span class="mono">${escapeHtml(model.tipoCambio)} Bs/$us</span></div>
      </div>

      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Peso Total (g)</div>
            <div style="font-weight:700;">${escapeHtml(model.pesoTotal)} g</div>
          </td>
          <td class="box">
            <div class="label">Merma (g)</div>
            <div style="font-weight:700;">${escapeHtml(model.merma)} g</div>
          </td>
          <td class="box">
            <div class="label">Peso en Oro (g)</div>
            <div style="font-weight:700;">${escapeHtml(model.pesoOro)} g</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Monto Prestado ($us)</div>
            <div style="font-weight:700;">${escapeHtml(model.montoPrestadoUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Monto Bolivianos (Bs)</div>
            <div style="font-weight:700;">${escapeHtml(model.montoPrestadoBs)} Bs</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${escapeHtml(model.cargoMensualUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${escapeHtml(model.cargoMensualBs)} Bs</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:60%">
            <div class="label">Concepto</div>
            <div>Cambio Dolares</div>
            <div class="muted">Cambio Dolares (asociado a prestamo ${escapeHtml(model.referencia)})</div>
          </td>
          <td class="box" style="width:40%">
            <div class="label">Doc. Nro.</div>
            <div class="mono">${escapeHtml(model.docSerie)} ${escapeHtml(model.docNro)}</div>
          </td>
        </tr>
      </table>

      <div class="box">
        <div class="label">Referencia de Prestamo</div>
        <div class="mono" style="font-weight:700;">${escapeHtml(model.referencia)}</div>
      </div>

      <table style="width:100%; margin-top:24px;">
        <tr>
          <td style="text-align:center; width:100%;" colspan="2">
            <br><br><br>
            <div style="border-top:1px solid #333; width:75%; margin:0 auto 4px;"></div>
            <div class="muted">Firma del Cliente</div>
          </td>
        </tr>
      </table>
    </div>
  `
}

export async function printPrestamoDirecto (axiosInstance, prestamoId, prestamoData = null) {
  const [prestamo, tipoCambio] = await Promise.all([
    prestamoData?.cliente ? Promise.resolve(prestamoData) : fetchPrestamo(axiosInstance, prestamoId),
    fetchTipoCambio(axiosInstance)
  ])

  const model = buildPrestamoModel(prestamo, tipoCambio)
  return printHtml(renderPrestamoContrato(model))
}

export async function printCambioMonedaDirecto (axiosInstance, prestamoId, prestamoData = null) {
  const [prestamo, tipoCambio] = await Promise.all([
    prestamoData?.cliente ? Promise.resolve(prestamoData) : fetchPrestamo(axiosInstance, prestamoId),
    fetchTipoCambio(axiosInstance)
  ])

  const model = buildPrestamoModel(prestamo, tipoCambio)
  return printHtml(renderCambioMoneda(model))
}
