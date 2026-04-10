import{h as d}from"./moment-C5S46NFB.js";import{P as h}from"./index-C5E5KQtJ.js";const x={nombre:"JOYERIA ROSARIO",sucursal:"ORURO",direccion:"DIR. ADOLFO MIER, POTOSI Y PAGADOR (LADO PALACE HOTEL)",cel:"73800584",telefono:"52-55713"},v=`
  @page { size: letter portrait; margin: 5mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; }
  .print-root { width: 100%; color: #111; }
  .order-double-sheet {
    height: calc(279.4mm - 10mm);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 2mm;
    overflow: hidden;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .sheet {
    border: 1.6px solid #d50000;
    border-radius: 12px;
    padding: 7px 8px 8px;
    height: calc((279.4mm - 10mm - 2mm) / 2);
    page-break-inside: avoid;
    break-inside: avoid;
    overflow: hidden;
  }
  .sheet.order-copy-top { margin-bottom: 0; }
  .sheet.order-copy-bottom { margin-bottom: 0; }
  .head { width: 100%; border-collapse: collapse; }
  .head td { vertical-align: top; }
  .center { text-align: center; }
  .right { text-align: right; }
  .middle { vertical-align: middle; }
  .brand { color: #d50000; }
  .title { font-size: 24.5px; font-weight: 800; letter-spacing: .15px; }
  .sub { font-size: 12.2px; line-height: 1.18; }
  .small { font-size: 12.1px; line-height: 1.18; }
  .medium { font-size: 14.2px; line-height: 1.24; }
  .large { font-size: 15.4px; line-height: 1.28; }
  .large-name { font-size: 18.2px; line-height: 1.18; }
  .bold { font-weight: 700; }
  .logo-box { width: 112px; text-align: center; }
  .logo-box img { max-width: 46px; max-height: 46px; display: block; margin: 0 auto 2px; object-fit: contain; }
  .fallback-image {
    width: 42px;
    height: 42px;
    object-fit: contain;
    display: inline-block;
  }
  .badge {
    display: inline-block;
    min-width: 100px;
    max-width: 108px;
    text-align: center;
    border: 1.3px solid #d50000;
    border-radius: 8px;
    padding: 3px 5px;
    font-size: 11.8px;
    line-height: 1.12;
    margin-bottom: 3px;
    word-break: break-word;
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
    padding: 8px 8px 7px;
    position: relative;
    vertical-align: top;
  }
  .label {
    position: absolute;
    top: -6px;
    left: 8px;
    background: #fff;
    padding: 0 4px;
    color: #d50000;
    font-size: 11.9px;
    font-weight: 700;
  }
  .note-text, .justify { text-align: justify; line-height: 1.15; }
  .info-pills {
    display: table;
    width: 100%;
    border-spacing: 4px 0;
    margin-top: 5px;
  }
  .info-pills .item {
    display: table-cell;
    width: 50%;
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 7px 6px;
    text-align: center;
    font-size: 11.5px;
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
  .dot { border-top: 1px dashed #bbb; margin: 3px 0 1px; }
  .customer-box {
    min-height: 44px;
    padding-top: 12px;
    padding-bottom: 10px;
  }
  .job-box {
    min-height: 86px;
    padding-top: 12px;
  }
  .sketch-box {
    min-height: 136px;
    padding-top: 12px;
  }
  .sketch-guide {
    margin-top: 8px;
    min-height: 96px;
    border: 1px dashed #e7c0c0;
    border-radius: 8px;
  }
  .reference-box {
    min-height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 8px;
    border: 1px dashed #e7c0c0;
    border-radius: 8px;
    padding: 6px;
    overflow: hidden;
    background: #fffdfd;
  }
  .reference-image {
    width: 100%;
    max-width: 100%;
    max-height: 98px;
    object-fit: contain;
    display: block;
  }
  .observaciones-box { min-height: 26px; }
  .signature-table { width: 100%; border-collapse: collapse; margin-top: 3px; }
  .signature-table td { width: 50%; text-align: center; font-size: 11.8px; }
  .signature-line { border-top: 1px solid #333; width: 75%; margin: 12px auto 3px; }
  .muted { color: #666; }
  .mt-6 { margin-top: 5px; }
  .order-copy-top .head { margin-bottom: 0; }
  .order-copy-top .cell { padding-top: 7px; padding-bottom: 6px; }
  .order-copy-top .grid { margin-top: 3px; }
  .half-separator {
    border-top: 1px dashed #999;
    height: 0;
  }
`;function a(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function l(e){return Number(e||0).toFixed(2)}function u(e,t="DD/MM/YYYY"){return e?d(e).format(t):"-"}function f(e){const t=e?.defaults?.baseURL||window.location.origin,i=new URL(t,window.location.origin);return i.pathname=i.pathname.replace(/\/api\/?$/,"/"),i.toString().replace(/\/$/,"")}function r(e,t){return t?`${e}/images/${t}`:""}function p(e,t,i){const s=f(i),o=d(e?.fecha_creacion||new Date),n=d();return{company:x,logo:r(s,"logo.png"),fallbackRings:r(s,"rings.png"),photo:r(s,e?.foto_modelo),numero:e?.numero||"",cliente:e?.cliente?.name||"N/A",detalle:[e?.detalle||"-",`Peso: ${e?.peso||0} gr.`,`Oro: ${e?.kilates18||"-"}`].join(" / "),costoTotal:l(e?.costo_total),adelanto:l(Number(e?.adelanto||0)+Number(e?.totalPagos||0)),saldo:l(e?.saldo),entrega:e?.fecha_entrega?u(e.fecha_entrega):"-",observaciones:e?.observacion||e?.observaciones||"",nota:e?.nota||"Ningun trabajo sera entregado sin la presente orden. Importante en caso de no recojo se espera un maximo de 90 dias antes de proceder a la fundicion de la joya",garantia:{codigo:e?.numero||"-",fecha:n.format("DD/MM/YYYY"),dia:n.format("DD"),mes:n.format("MM"),ano:n.format("YYYY"),cliente:e?.cliente?.name||"N/A",tipo:"Joya",periodo:"1 ano",detalle:e?.detalle||"-",mantenimientoMeses:12,precioOro:l(t)},fechaTrabajo:{hoy:n.format("DD/MM/YYYY"),dia:o.format("DD"),mes:o.format("MM"),ano:o.format("YYYY")}}}function c(e,t="top"){const i=e.photo?`<div class="reference-box"><img class="reference-image" src="${a(e.photo)}" alt="Foto de referencia"></div>`:`
      <div class="small muted">Espacio para dibujo o detalle del cliente</div>
      <div class="sketch-guide"></div>
    `;return`
    <div class="sheet order-copy-${a(t)}">
      <table class="head">
        <tr>
          <td class="logo-box">
            <img src="${a(e.logo)}" alt="Logo">
            <div class="small">
              Calidad y garantia<br>
              Oro 18 Klts<br>
              Plata 925 decimos
            </div>
          </td>
          <td class="center">
            <div class="small">${a(e.fechaTrabajo.hoy)}</div>
            <div style="height:6px"></div>
            <div class="brand title">ORDEN DE TRABAJO</div>
            <div class="sub">
              ${a(e.company.direccion)}<br>
              CEL. ${a(e.company.cel)} TELF. ${a(e.company.telefono)}<br>
              ${a(e.company.sucursal)} - Bolivia
            </div>
          </td>
          <td class="right" style="width:174px">
            <table style="width:100%">
              <tr>
                <td class="right middle" style="width:110px">
                  <div class="badge"><b>Nro: ${a(e.numero)}</b></div>
                  <div class="badge"><b>Bs.</b> ${a(e.costoTotal)}</div>
                </td>
                <td class="right" style="padding-left:4px">
                  <img class="fallback-image" src="${a(e.fallbackRings)}" alt="Rings">
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell customer-box" style="width:54%">
            <span class="label">El Senor:</span>
            <div class="large-name center">${a(String(e.cliente).toUpperCase())}</div>
          </td>
          <td class="cell sketch-box" rowspan="2" style="width:46%">
            <span class="label">Grafico / Referencia:</span>
            ${i}
          </td>
        </tr>
        <tr>
          <td class="cell job-box" style="width:54%">
            <span class="label">Trabajo de Joya:</span>
            <div class="large">${a(e.detalle)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:33.33%">
            <span class="label">Costo Total</span>
            <div class="center large bold">${a(e.costoTotal)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">A cuenta</span>
            <div class="center large bold">${a(e.adelanto)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">Saldo</span>
            <div class="center large bold">${a(e.saldo)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:40%">
            <span class="label">Fecha de Entrega:</span>
            <div class="center large bold">${a(e.entrega)}</div>
          </td>
          <td class="cell observaciones-box" style="width:60%">
            <span class="label">Observaciones:</span>
            <div class="small">${a(e.observaciones)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">NOTA</span>
        <div class="small note-text">${a(e.nota)}</div>
      </div>

      <div class="dot"></div>
    </div>
  `}function y(e){return`
    <div class="sheet">
      <table class="head">
        <tr>
          <td style="width:82px">
            <img src="${a(e.logo)}" alt="Logo" style="max-width:58px;max-height:58px;object-fit:contain;">
          </td>
          <td class="center">
            <div class="brand bold medium">${a(e.company.nombre)}</div>
            <div class="small">${a(e.company.direccion)}</div>
            <div class="small">Cel: ${a(e.company.cel)} - ${a(e.company.sucursal)}</div>
            <div class="brand title" style="margin-top:6px">GARANTIA</div>
            <div class="small muted">Cobertura por defectos de fabricacion segun condiciones indicadas</div>
          </td>
          <td class="right" style="width:138px">
            <div class="badge">
              <b>Bs.</b> ${a(e.garantia.precioOro)}<br>
              <span class="small muted">${a(e.garantia.fecha)}</span>
            </div>
            <div class="badge">Codigo: ${a(e.garantia.codigo)}</div>
            <img class="fallback-image" src="${a(e.fallbackRings)}" alt="Rings">
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:55%">
            <span class="label">Cliente</span>
            <div class="medium">${a(String(e.garantia.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:45%">
            <span class="label">Fecha</span>
            <div class="medium">
              Dia <span class="date-box">${a(e.garantia.dia)}</span>
              Mes <span class="date-box">${a(e.garantia.mes)}</span>
              Ano <span class="date-box">${a(e.garantia.ano)}</span>
            </div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Detalle de la pieza / trabajo</span>
        <div class="medium">${a(e.garantia.detalle)}</div>
        <div class="small muted mt-6">
          <b>Tipo:</b> ${a(e.garantia.tipo)}
          &nbsp;&nbsp;|&nbsp;&nbsp;
          <b>Periodo:</b> ${a(e.garantia.periodo)}
        </div>
      </div>

      <div class="info-pills">
        <div class="item">Mantenimiento sin costo: ${a(e.garantia.mantenimientoMeses)} meses</div>
        <div class="item">Sucursal: ${a(e.company.sucursal)}</div>
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
  `}async function g(e,t){const{data:i}=await e.get(`ordenes/${t}`);return i}async function b(e){try{const{data:t}=await e.get("cogs/2");return Number(t?.value||t?.valor||t||0)}catch{return 0}}function w(e){const t=Array.from(e?.contentDocument?.images||[]);return t.length?Promise.all(t.map(i=>i.complete?Promise.resolve():new Promise(s=>{i.onload=()=>s(),i.onerror=()=>s()}))):Promise.resolve()}function m(e){const t=document.createElement("div");t.className="print-root",t.innerHTML=e;const i=new h;return new Promise(s=>{i.print(t,[v],[],async({iframe:o,launchPrint:n})=>{await w(o),setTimeout(()=>{n(),s()},120)})})}async function k(e,t,i=null){const[s,o]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(e,t),b(e)]),n=p(s,o,e);return m(`
    <div class="order-double-sheet">
      ${c(n,"top")}
      <div class="half-separator"></div>
      ${c(n,"bottom")}
    </div>
  `)}async function Y(e,t,i=null){const[s,o]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(e,t),b(e)]),n=p(s,o,e);return m(y(n))}export{k as a,Y as p};
