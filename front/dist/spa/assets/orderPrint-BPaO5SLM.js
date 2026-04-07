import{h as d}from"./moment-C5S46NFB.js";import{P as h}from"./index-C5E5KQtJ.js";const v={nombre:"JOYERIA ROSARIO",sucursal:"ORURO",direccion:"DIR. ADOLFO MIER, POTOSI Y PAGADOR (LADO PALACE HOTEL)",cel:"73800584",telefono:"52-55713"},u=`
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
  .brand { color: #d50000; }
  .title { font-size: 19.6px; font-weight: 800; letter-spacing: .15px; }
  .sub { font-size: 9.8px; line-height: 1.15; }
  .small { font-size: 9.7px; line-height: 1.15; }
  .medium { font-size: 11.2px; line-height: 1.2; }
  .large { font-size: 12.1px; line-height: 1.24; }
  .large-name { font-size: 14.1px; line-height: 1.15; }
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
    min-width: 100px;
    max-width: 108px;
    text-align: center;
    border: 1.3px solid #d50000;
    border-radius: 8px;
    padding: 3px 5px;
    font-size: 9.8px;
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
    padding: 6px 6px 5px;
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
    font-size: 9.5px;
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
    font-size: 9.6px;
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
  .sketch-box { min-height: 70px; }
  .sketch-guide {
    margin-top: 4px;
    min-height: 40px;
    border: 1px dashed #e7c0c0;
    border-radius: 8px;
  }
  .observaciones-box { min-height: 26px; }
  .signature-table { width: 100%; border-collapse: collapse; margin-top: 3px; }
  .signature-table td { width: 50%; text-align: center; font-size: 9.8px; }
  .signature-line { border-top: 1px solid #333; width: 75%; margin: 12px auto 3px; }
  .muted { color: #666; }
  .mt-6 { margin-top: 3px; }
  .order-copy-top .head { margin-bottom: 0; }
  .order-copy-top .cell { padding-top: 7px; padding-bottom: 6px; }
  .order-copy-top .grid { margin-top: 3px; }
  .half-separator {
    border-top: 1px dashed #999;
    height: 0;
  }
`;function e(a){return String(a??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function n(a){return Number(a||0).toFixed(2)}function x(a,t="DD/MM/YYYY"){return a?d(a).format(t):"-"}function f(a){const t=a?.defaults?.baseURL||window.location.origin,i=new URL(t,window.location.origin);return i.pathname=i.pathname.replace(/\/api\/?$/,"/"),i.toString().replace(/\/$/,"")}function r(a,t){return t?`${a}/images/${t}`:""}function p(a,t,i){const s=f(i),o=d(a?.fecha_creacion||new Date),l=d();return{company:v,logo:r(s,"logo.png"),fallbackRings:r(s,"rings.png"),photo:r(s,a?.foto_modelo),numero:a?.numero||"",cliente:a?.cliente?.name||"N/A",detalle:[a?.detalle||"-",`Peso: ${a?.peso||0} gr.`,`Oro: ${a?.kilates18||"-"}`].join(" / "),costoTotal:n(a?.costo_total),adelanto:n(Number(a?.adelanto||0)+Number(a?.totalPagos||0)),saldo:n(a?.saldo),entrega:a?.fecha_entrega?x(a.fecha_entrega):"-",observaciones:a?.observacion||a?.observaciones||"",nota:a?.nota||"Ningun trabajo sera entregado sin la presente orden. Importante en caso de no recojo se espera un maximo de 90 dias antes de proceder a la fundicion de la joya",garantia:{codigo:a?.numero||"-",fecha:l.format("DD/MM/YYYY"),dia:l.format("DD"),mes:l.format("MM"),ano:l.format("YYYY"),cliente:a?.cliente?.name||"N/A",tipo:"Joya",periodo:"1 ano",detalle:a?.detalle||"-",mantenimientoMeses:12,precioOro:n(t)},fechaTrabajo:{hoy:l.format("DD/MM/YYYY"),dia:o.format("DD"),mes:o.format("MM"),ano:o.format("YYYY")}}}function c(a,t="top"){const i=a.photo?`<img class="side-image" src="${e(a.photo)}" alt="Foto de modelo">`:`<img class="fallback-image" src="${e(a.fallbackRings)}" alt="Rings">`;return`
    <div class="sheet order-copy-${e(t)}">
      <table class="head">
        <tr>
          <td class="logo-box">
            <img src="${e(a.logo)}" alt="Logo">
            <div class="small">
              Calidad y garantia<br>
              Oro 18 Klts<br>
              Plata 925 decimos
            </div>
          </td>
          <td class="center">
            <div class="small">${e(a.fechaTrabajo.hoy)}</div>
            <div style="height:6px"></div>
            <div class="brand title">ORDEN DE TRABAJO</div>
            <div class="sub">
              ${e(a.company.direccion)}<br>
              CEL. ${e(a.company.cel)} TELF. ${e(a.company.telefono)}<br>
              ${e(a.company.sucursal)} - Bolivia
            </div>
          </td>
          <td class="right" style="width:174px">
            <table style="width:100%">
              <tr>
                <td class="right" style="padding-right:6px">${i}</td>
                <td class="right" style="width:110px">
                  <div class="badge"><b>Nro: ${e(a.numero)}</b></div>
                  <div class="badge"><b>Bs.</b> ${e(a.costoTotal)}</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:100%">
            <span class="label">El Senor:</span>
            <div class="large-name center">${e(String(a.cliente).toUpperCase())}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:64%">
            <span class="label">Trabajo de Joya:</span>
            <div class="large">${e(a.detalle)}</div>
          </td>
          <td class="cell sketch-box" style="width:36%">
            <span class="label">Grafico / Referencia:</span>
            <div class="small muted">Espacio para dibujo o detalle del cliente</div>
            <div class="sketch-guide"></div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:33.33%">
            <span class="label">Costo Total</span>
            <div class="center large bold">${e(a.costoTotal)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">A cuenta</span>
            <div class="center large bold">${e(a.adelanto)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">Saldo</span>
            <div class="center large bold">${e(a.saldo)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:40%">
            <span class="label">Fecha de Entrega:</span>
            <div class="center large bold">${e(a.entrega)}</div>
          </td>
          <td class="cell observaciones-box" style="width:60%">
            <span class="label">Observaciones:</span>
            <div class="small">${e(a.observaciones)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">NOTA</span>
        <div class="small note-text">${e(a.nota)}</div>
      </div>

      <div class="dot"></div>
    </div>
  `}function y(a){return`
    <div class="sheet">
      <table class="head">
        <tr>
          <td style="width:82px">
            <img src="${e(a.logo)}" alt="Logo" style="max-width:58px;max-height:58px;object-fit:contain;">
          </td>
          <td class="center">
            <div class="brand bold medium">${e(a.company.nombre)}</div>
            <div class="small">${e(a.company.direccion)}</div>
            <div class="small">Cel: ${e(a.company.cel)} - ${e(a.company.sucursal)}</div>
            <div class="brand title" style="margin-top:6px">GARANTIA</div>
            <div class="small muted">Cobertura por defectos de fabricacion segun condiciones indicadas</div>
          </td>
          <td class="right" style="width:138px">
            <div class="badge">
              <b>Bs.</b> ${e(a.garantia.precioOro)}<br>
              <span class="small muted">${e(a.garantia.fecha)}</span>
            </div>
            <div class="badge">Codigo: ${e(a.garantia.codigo)}</div>
            <img class="fallback-image" src="${e(a.fallbackRings)}" alt="Rings">
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:55%">
            <span class="label">Cliente</span>
            <div class="medium">${e(String(a.garantia.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:45%">
            <span class="label">Fecha</span>
            <div class="medium">
              Dia <span class="date-box">${e(a.garantia.dia)}</span>
              Mes <span class="date-box">${e(a.garantia.mes)}</span>
              Ano <span class="date-box">${e(a.garantia.ano)}</span>
            </div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Detalle de la pieza / trabajo</span>
        <div class="medium">${e(a.garantia.detalle)}</div>
        <div class="small muted mt-6">
          <b>Tipo:</b> ${e(a.garantia.tipo)}
          &nbsp;&nbsp;|&nbsp;&nbsp;
          <b>Periodo:</b> ${e(a.garantia.periodo)}
        </div>
      </div>

      <div class="info-pills">
        <div class="item">Mantenimiento sin costo: ${e(a.garantia.mantenimientoMeses)} meses</div>
        <div class="item">Sucursal: ${e(a.company.sucursal)}</div>
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
  `}async function g(a,t){const{data:i}=await a.get(`ordenes/${t}`);return i}async function b(a){try{const{data:t}=await a.get("cogs/2");return Number(t?.value||t?.valor||t||0)}catch{return 0}}function w(a){const t=Array.from(a?.contentDocument?.images||[]);return t.length?Promise.all(t.map(i=>i.complete?Promise.resolve():new Promise(s=>{i.onload=()=>s(),i.onerror=()=>s()}))):Promise.resolve()}function m(a){const t=document.createElement("div");t.className="print-root",t.innerHTML=a;const i=new h;return new Promise(s=>{i.print(t,[u],[],async({iframe:o,launchPrint:l})=>{await w(o),setTimeout(()=>{l(),s()},120)})})}async function k(a,t,i=null){const[s,o]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(a,t),b(a)]),l=p(s,o,a);return m(`
    <div class="order-double-sheet">
      ${c(l,"top")}
      <div class="half-separator"></div>
      ${c(l,"bottom")}
    </div>
  `)}async function Y(a,t,i=null){const[s,o]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(a,t),b(a)]),l=p(s,o,a);return m(y(l))}export{k as a,Y as p};
