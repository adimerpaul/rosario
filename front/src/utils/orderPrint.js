import moment from 'moment'
import Printd from 'printd'

const COMPANY = {
  nombre: 'JOYERIA ROSARIO',
  sucursal: 'ORURO',
  direccion: 'DIR. ADOLFO MIER, POTOSI Y PAGADOR (LADO PALACE HOTEL)',
  cel: '73800584',
  telefono: '52-55713'
}

const DIRECT_PRINT_STYLES = `
  @page { size: letter portrait; margin: 6mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; }
  .print-root { width: 100%; color: #111; }
  .sheet {
    border: 1.6px solid #d50000;
    border-radius: 12px;
    padding: 6px;
    margin-bottom: 12px;
    page-break-inside: avoid;
  }
  .head { width: 100%; border-collapse: collapse; }
  .head td { vertical-align: top; }
  .center { text-align: center; }
  .right { text-align: right; }
  .brand { color: #d50000; }
  .title { font-size: 18px; font-weight: 800; letter-spacing: .1px; }
  .sub { font-size: 8.5px; line-height: 1.1; }
  .small { font-size: 8.5px; }
  .medium { font-size: 10.5px; }
  .large { font-size: 10.5px; }
  .bold { font-weight: 700; }
  .logo-box { width: 112px; text-align: center; }
  .logo-box img { max-width: 46px; max-height: 46px; display: block; margin: 0 auto 2px; object-fit: contain; }
  .side-image {
    width: 64px;
    height: 64px;
    border: 1.2px solid #d50000;
    border-radius: 6px;
    object-fit: cover;
    display: inline-block;
  }
  .fallback-image {
    width: 42px;
    height: 42px;
    object-fit: contain;
    display: inline-block;
  }
  .badge {
    display: inline-block;
    min-width: 72px;
    text-align: center;
    border: 1.3px solid #d50000;
    border-radius: 8px;
    padding: 2px 5px;
    font-size: 9.5px;
    line-height: 1.05;
    margin-bottom: 3px;
  }
  .grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 3px;
    margin-top: 3px;
  }
  .cell {
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 6px 6px 5px;
    position: relative;
  }
  .label {
    position: absolute;
    top: -6px;
    left: 8px;
    background: #fff;
    padding: 0 4px;
    color: #d50000;
    font-size: 9px;
    font-weight: 700;
  }
  .note-text, .justify { text-align: justify; line-height: 1.05; }
  .info-pills {
    display: table;
    width: 100%;
    border-spacing: 3px 0;
    margin-top: 4px;
  }
  .info-pills .item {
    display: table-cell;
    width: 50%;
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 6px;
    text-align: center;
    font-size: 9px;
    font-weight: 700;
  }
  .date-box {
    display: inline-block;
    border: 1.5px solid #d50000;
    border-radius: 6px;
    padding: 2px 6px;
    min-width: 34px;
    text-align: center;
    font-weight: 700;
    margin: 0 2px;
  }
  .dot { border-top: 1px dashed #bbb; margin: 4px 0; }
  .signature-table { width: 100%; border-collapse: collapse; margin-top: 3px; }
  .signature-table td { width: 50%; text-align: center; font-size: 9.5px; }
  .signature-line { border-top: 1px solid #333; width: 75%; margin: 12px auto 3px; }
  .muted { color: #666; }
  .mt-6 { margin-top: 3px; }
  .half-separator {
    height: 34px;
    border-top: 1px dashed #999;
    margin: 4px 0 6px;
  }
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

function formatDate (value, format = 'DD/MM/YYYY') {
  if (!value) return '-'
  return moment(value).format(format)
}

function formatPhone (order) {
  return order?.cliente?.cellphone || order?.celular || 'N/A'
}

function normalizeBaseUrl (axiosInstance) {
  const rawBase = axiosInstance?.defaults?.baseURL || window.location.origin
  const url = new URL(rawBase, window.location.origin)
  url.pathname = url.pathname.replace(/\/api\/?$/, '/')
  return url.toString().replace(/\/$/, '')
}

function imageUrl (baseUrl, fileName) {
  if (!fileName) return ''
  return `${baseUrl}/images/${fileName}`
}

function buildOrderPrintModel (order, precioOro, axiosInstance) {
  const baseUrl = normalizeBaseUrl(axiosInstance)
  const creationDate = moment(order?.fecha_creacion || new Date())
  const today = moment()

  return {
    company: COMPANY,
    logo: imageUrl(baseUrl, 'logo.png'),
    fallbackRings: imageUrl(baseUrl, 'rings.png'),
    photo: imageUrl(baseUrl, order?.foto_modelo),
    numero: order?.numero || '',
    cliente: order?.cliente?.name || 'N/A',
    telefono: formatPhone(order),
    detalle: [
      order?.detalle || '-',
      `Peso: ${order?.peso || 0} gr.`,
      `Oro: ${order?.kilates18 || '-'}`
    ].join(' / '),
    costoTotal: money(order?.costo_total),
    adelanto: money(Number(order?.adelanto || 0) + Number(order?.totalPagos || 0)),
    saldo: money(order?.saldo),
    entrega: order?.fecha_entrega ? formatDate(order.fecha_entrega) : '-',
    nota: order?.nota || 'Ningun trabajo sera entregado sin la presente orden. Importante en caso de no recojo se espera un maximo de 90 dias antes de proceder a la fundicion de la joya',
    garantia: {
      codigo: order?.numero || '-',
      fecha: today.format('DD/MM/YYYY'),
      dia: today.format('DD'),
      mes: today.format('MM'),
      ano: today.format('YYYY'),
      cliente: order?.cliente?.name || 'N/A',
      tipo: 'Joya',
      periodo: '1 ano',
      detalle: order?.detalle || '-',
      mantenimientoMeses: 12,
      precioOro: money(precioOro)
    },
    fechaTrabajo: {
      hoy: today.format('DD/MM/YYYY'),
      dia: creationDate.format('DD'),
      mes: creationDate.format('MM'),
      ano: creationDate.format('YYYY')
    }
  }
}

function renderOrderWorkSheet (model) {
  const photoHtml = model.photo
    ? `<img class="side-image" src="${escapeHtml(model.photo)}" alt="Foto de modelo">`
    : `<img class="fallback-image" src="${escapeHtml(model.fallbackRings)}" alt="Rings">`

  return `
    <div class="sheet">
      <table class="head">
        <tr>
          <td class="logo-box">
            <img src="${escapeHtml(model.logo)}" alt="Logo">
            <div class="small">
              Calidad y garantia<br>
              Oro 18 Klts<br>
              Plata 925 decimos
            </div>
          </td>
          <td class="center">
            <div class="small">${escapeHtml(model.fechaTrabajo.hoy)}</div>
            <div style="height:6px"></div>
            <div class="brand title">ORDEN DE TRABAJO</div>
            <div class="sub">
              ${escapeHtml(model.company.direccion)}<br>
              CEL. ${escapeHtml(model.company.cel)} TELF. ${escapeHtml(model.company.telefono)}<br>
              ${escapeHtml(model.company.sucursal)} - Bolivia
            </div>
          </td>
          <td class="right" style="width:116px">
            <table style="width:100%">
              <tr>
                <td class="right">${photoHtml}</td>
                <td class="right" style="width:62px">
                  <div class="badge"><b>Nro: ${escapeHtml(model.numero)}</b></div>
                  <div class="badge"><b>Bs.</b> ${escapeHtml(model.costoTotal)}</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:50%">
            <span class="label">El Senor:</span>
            <div class="large center">${escapeHtml(String(model.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:50%">
            <span class="label">Telefono:</span>
            <div class="large center">${escapeHtml(model.telefono)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Trabajo de Joya:</span>
        <div class="large">${escapeHtml(model.detalle)}</div>
      </div>

      <table class="grid">
        <tr>
          <td class="cell" style="width:33.33%">
            <span class="label">Costo Total</span>
            <div class="center large bold">${escapeHtml(model.costoTotal)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">A cuenta</span>
            <div class="center large bold">${escapeHtml(model.adelanto)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">Saldo</span>
            <div class="center large bold">${escapeHtml(model.saldo)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Fecha de Entrega:</span>
        <div class="center large bold">${escapeHtml(model.entrega)}</div>
      </div>

      <div class="cell mt-6">
        <span class="label">NOTA</span>
        <div class="small note-text">${escapeHtml(model.nota)}</div>
      </div>

      <div class="dot"></div>
    </div>
  `
}

function renderWarrantySheet (model) {
  return `
    <div class="sheet">
      <table class="head">
        <tr>
          <td style="width:82px">
            <img src="${escapeHtml(model.logo)}" alt="Logo" style="max-width:58px;max-height:58px;object-fit:contain;">
          </td>
          <td class="center">
            <div class="brand bold medium">${escapeHtml(model.company.nombre)}</div>
            <div class="small">${escapeHtml(model.company.direccion)}</div>
            <div class="small">Cel: ${escapeHtml(model.company.cel)} - ${escapeHtml(model.company.sucursal)}</div>
            <div class="brand title" style="margin-top:6px">GARANTIA</div>
            <div class="small muted">Cobertura por defectos de fabricacion segun condiciones indicadas</div>
          </td>
          <td class="right" style="width:138px">
            <div class="badge">
              <b>Bs.</b> ${escapeHtml(model.garantia.precioOro)}<br>
              <span class="small muted">${escapeHtml(model.garantia.fecha)}</span>
            </div>
            <div class="badge">Codigo: ${escapeHtml(model.garantia.codigo)}</div>
            <img class="fallback-image" src="${escapeHtml(model.fallbackRings)}" alt="Rings">
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:55%">
            <span class="label">Cliente</span>
            <div class="medium">${escapeHtml(String(model.garantia.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:45%">
            <span class="label">Fecha</span>
            <div class="medium">
              Dia <span class="date-box">${escapeHtml(model.garantia.dia)}</span>
              Mes <span class="date-box">${escapeHtml(model.garantia.mes)}</span>
              Ano <span class="date-box">${escapeHtml(model.garantia.ano)}</span>
            </div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Detalle de la pieza / trabajo</span>
        <div class="medium">${escapeHtml(model.garantia.detalle)}</div>
        <div class="small muted mt-6">
          <b>Tipo:</b> ${escapeHtml(model.garantia.tipo)}
          &nbsp;&nbsp;|&nbsp;&nbsp;
          <b>Periodo:</b> ${escapeHtml(model.garantia.periodo)}
        </div>
      </div>

      <div class="info-pills">
        <div class="item">Mantenimiento sin costo: ${escapeHtml(model.garantia.mantenimientoMeses)} meses</div>
        <div class="item">Sucursal: ${escapeHtml(model.company.sucursal)}</div>
      </div>

      <div class="cell mt-6">
        <span class="label">Condiciones de Garantia</span>
        <div class="small justify">
          La garantia cubre <b>defectos de fabricacion</b> del metal y soldaduras durante el periodo indicado.
          No cubre golpes, rayones, deformaciones por uso, exposicion a quimicos, humedad o temperaturas extremas,
          perdida de piedras por impacto ni intervenciones de terceros. Es indispensable presentar este documento
          para hacer valida la garantia.
        </div>
        <div class="center small mt-6">Gracias por su preferencia</div>
      </div>

      <div class="dot"></div>
      <table class="signature-table">
        <tr>
          <td>
            <div class="signature-line"></div>
            <div>Firma Cliente</div>
          </td>
          <td>
            <div class="signature-line"></div>
            <div>Firma y sello</div>
          </td>
        </tr>
      </table>
    </div>
  `
}

async function fetchOrder (axiosInstance, orderId) {
  const { data } = await axiosInstance.get(`ordenes/${orderId}`)
  return data
}

async function fetchGoldPrice (axiosInstance) {
  try {
    const { data } = await axiosInstance.get('cogs/2')
    return Number(data?.value || data?.valor || data || 0)
  } catch {
    return 0
  }
}

function waitForPrintAssets (iframe) {
  const images = Array.from(iframe?.contentDocument?.images || [])
  if (!images.length) return Promise.resolve()

  return Promise.all(images.map(img => {
    if (img.complete) return Promise.resolve()
    return new Promise(resolve => {
      img.onload = () => resolve()
      img.onerror = () => resolve()
    })
  }))
}

function printHtml (html) {
  const root = document.createElement('div')
  root.className = 'print-root'
  root.innerHTML = html
  const printer = new Printd()

  return new Promise((resolve) => {
    printer.print(root, [DIRECT_PRINT_STYLES], [], async ({ iframe, launchPrint }) => {
      await waitForPrintAssets(iframe)
      setTimeout(() => {
        launchPrint()
        resolve()
      }, 120)
    })
  })
}

export async function printOrdenTrabajoDirecto (axiosInstance, orderId, orderData = null) {
  const [orden, precioOro] = await Promise.all([
    orderData?.cliente?.cellphone ? Promise.resolve(orderData) : fetchOrder(axiosInstance, orderId),
    fetchGoldPrice(axiosInstance)
  ])

  const model = buildOrderPrintModel(orden, precioOro, axiosInstance)
  return printHtml(`
    ${renderOrderWorkSheet(model)}
    <div class="half-separator"></div>
    ${renderOrderWorkSheet(model)}
  `)
}

export async function printGarantiaDirecta (axiosInstance, orderId, orderData = null) {
  const [orden, precioOro] = await Promise.all([
    orderData?.cliente?.cellphone ? Promise.resolve(orderData) : fetchOrder(axiosInstance, orderId),
    fetchGoldPrice(axiosInstance)
  ])

  const model = buildOrderPrintModel(orden, precioOro, axiosInstance)
  return printHtml(renderWarrantySheet(model))
}
