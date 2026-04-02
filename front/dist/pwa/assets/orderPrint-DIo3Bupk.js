import{h as d}from"./moment-C5S46NFB.js";import{P as h}from"./index-C5E5KQtJ.js";const u={nombre:"JOYERIA ROSARIO",sucursal:"ORURO",direccion:"DIR. ADOLFO MIER, POTOSI Y PAGADOR (LADO PALACE HOTEL)",cel:"73800584",telefono:"52-55713"},v=`
  @page { size: letter portrait; margin: 6mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; }
  .print-root { width: 100%; color: #111; }
  .order-double-sheet {
    height: calc(279.4mm - 12mm);
    position: relative;
  }
  .sheet {
    border: 1.6px solid #d50000;
    border-radius: 12px;
    padding: 7px 8px;
    margin-bottom: 12px;
    page-break-inside: avoid;
  }
  .sheet.order-copy-top { margin-bottom: 0; }
  .sheet.order-copy-bottom {
    position: absolute;
    top: calc(50% + 3mm);
    left: 0;
    right: 0;
    margin-bottom: 0;
  }
  .head { width: 100%; border-collapse: collapse; }
  .head td { vertical-align: top; }
  .center { text-align: center; }
  .right { text-align: right; }
  .brand { color: #d50000; }
  .title { font-size: 19px; font-weight: 800; letter-spacing: .15px; }
  .sub { font-size: 9.2px; line-height: 1.15; }
  .small { font-size: 9.2px; line-height: 1.15; }
  .medium { font-size: 11.2px; line-height: 1.2; }
  .large { font-size: 11.4px; line-height: 1.2; }
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
    padding: 3px 5px;
    font-size: 10px;
    line-height: 1.12;
    margin-bottom: 3px;
  }
  .grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 4px;
    margin-top: 4px;
  }
  .cell {
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 7px 7px 6px;
    position: relative;
  }
  .label {
    position: absolute;
    top: -6px;
    left: 8px;
    background: #fff;
    padding: 0 4px;
    color: #d50000;
    font-size: 9.4px;
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
  .dot { border-top: 1px dashed #bbb; margin: 5px 0 2px; }
  .signature-table { width: 100%; border-collapse: collapse; margin-top: 3px; }
  .signature-table td { width: 50%; text-align: center; font-size: 9.8px; }
  .signature-line { border-top: 1px solid #333; width: 75%; margin: 12px auto 3px; }
  .muted { color: #666; }
  .mt-6 { margin-top: 5px; }
  .order-copy-top .head { margin-bottom: 1px; }
  .order-copy-top .cell { padding-top: 8px; padding-bottom: 7px; }
  .order-copy-top .grid { margin-top: 5px; }
  .half-separator {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 12px;
    border-top: 1px dashed #999;
    margin: 6px 0 0;
  }
`;function t(a){return String(a??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function n(a){return Number(a||0).toFixed(2)}function x(a,e="DD/MM/YYYY"){return a?d(a).format(e):"-"}function f(a){return a?.cliente?.cellphone||a?.celular||"N/A"}function y(a){const e=a?.defaults?.baseURL||window.location.origin,i=new URL(e,window.location.origin);return i.pathname=i.pathname.replace(/\/api\/?$/,"/"),i.toString().replace(/\/$/,"")}function r(a,e){return e?`${a}/images/${e}`:""}function p(a,e,i){const s=y(i),o=d(a?.fecha_creacion||new Date),l=d();return{company:u,logo:r(s,"logo.png"),fallbackRings:r(s,"rings.png"),photo:r(s,a?.foto_modelo),numero:a?.numero||"",cliente:a?.cliente?.name||"N/A",telefono:f(a),detalle:[a?.detalle||"-",`Peso: ${a?.peso||0} gr.`,`Oro: ${a?.kilates18||"-"}`].join(" / "),costoTotal:n(a?.costo_total),adelanto:n(Number(a?.adelanto||0)+Number(a?.totalPagos||0)),saldo:n(a?.saldo),entrega:a?.fecha_entrega?x(a.fecha_entrega):"-",nota:a?.nota||"Ningun trabajo sera entregado sin la presente orden. Importante en caso de no recojo se espera un maximo de 90 dias antes de proceder a la fundicion de la joya",garantia:{codigo:a?.numero||"-",fecha:l.format("DD/MM/YYYY"),dia:l.format("DD"),mes:l.format("MM"),ano:l.format("YYYY"),cliente:a?.cliente?.name||"N/A",tipo:"Joya",periodo:"1 ano",detalle:a?.detalle||"-",mantenimientoMeses:12,precioOro:n(e)},fechaTrabajo:{hoy:l.format("DD/MM/YYYY"),dia:o.format("DD"),mes:o.format("MM"),ano:o.format("YYYY")}}}function c(a,e="top"){const i=a.photo?`<img class="side-image" src="${t(a.photo)}" alt="Foto de modelo">`:`<img class="fallback-image" src="${t(a.fallbackRings)}" alt="Rings">`;return`
    <div class="sheet order-copy-${t(e)}">
      <table class="head">
        <tr>
          <td class="logo-box">
            <img src="${t(a.logo)}" alt="Logo">
            <div class="small">
              Calidad y garantia<br>
              Oro 18 Klts<br>
              Plata 925 decimos
            </div>
          </td>
          <td class="center">
            <div class="small">${t(a.fechaTrabajo.hoy)}</div>
            <div style="height:6px"></div>
            <div class="brand title">ORDEN DE TRABAJO</div>
            <div class="sub">
              ${t(a.company.direccion)}<br>
              CEL. ${t(a.company.cel)} TELF. ${t(a.company.telefono)}<br>
              ${t(a.company.sucursal)} - Bolivia
            </div>
          </td>
          <td class="right" style="width:116px">
            <table style="width:100%">
              <tr>
                <td class="right">${i}</td>
                <td class="right" style="width:62px">
                  <div class="badge"><b>Nro: ${t(a.numero)}</b></div>
                  <div class="badge"><b>Bs.</b> ${t(a.costoTotal)}</div>
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
            <div class="large center">${t(String(a.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:50%">
            <span class="label">Telefono:</span>
            <div class="large center">${t(a.telefono)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Trabajo de Joya:</span>
        <div class="large">${t(a.detalle)}</div>
      </div>

      <table class="grid">
        <tr>
          <td class="cell" style="width:33.33%">
            <span class="label">Costo Total</span>
            <div class="center large bold">${t(a.costoTotal)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">A cuenta</span>
            <div class="center large bold">${t(a.adelanto)}</div>
          </td>
          <td class="cell" style="width:33.33%">
            <span class="label">Saldo</span>
            <div class="center large bold">${t(a.saldo)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Fecha de Entrega:</span>
        <div class="center large bold">${t(a.entrega)}</div>
      </div>

      <div class="cell mt-6">
        <span class="label">NOTA</span>
        <div class="small note-text">${t(a.nota)}</div>
      </div>

      <div class="dot"></div>
    </div>
  `}function w(a){return`
    <div class="sheet">
      <table class="head">
        <tr>
          <td style="width:82px">
            <img src="${t(a.logo)}" alt="Logo" style="max-width:58px;max-height:58px;object-fit:contain;">
          </td>
          <td class="center">
            <div class="brand bold medium">${t(a.company.nombre)}</div>
            <div class="small">${t(a.company.direccion)}</div>
            <div class="small">Cel: ${t(a.company.cel)} - ${t(a.company.sucursal)}</div>
            <div class="brand title" style="margin-top:6px">GARANTIA</div>
            <div class="small muted">Cobertura por defectos de fabricacion segun condiciones indicadas</div>
          </td>
          <td class="right" style="width:138px">
            <div class="badge">
              <b>Bs.</b> ${t(a.garantia.precioOro)}<br>
              <span class="small muted">${t(a.garantia.fecha)}</span>
            </div>
            <div class="badge">Codigo: ${t(a.garantia.codigo)}</div>
            <img class="fallback-image" src="${t(a.fallbackRings)}" alt="Rings">
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="cell" style="width:55%">
            <span class="label">Cliente</span>
            <div class="medium">${t(String(a.garantia.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:45%">
            <span class="label">Fecha</span>
            <div class="medium">
              Dia <span class="date-box">${t(a.garantia.dia)}</span>
              Mes <span class="date-box">${t(a.garantia.mes)}</span>
              Ano <span class="date-box">${t(a.garantia.ano)}</span>
            </div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Detalle de la pieza / trabajo</span>
        <div class="medium">${t(a.garantia.detalle)}</div>
        <div class="small muted mt-6">
          <b>Tipo:</b> ${t(a.garantia.tipo)}
          &nbsp;&nbsp;|&nbsp;&nbsp;
          <b>Periodo:</b> ${t(a.garantia.periodo)}
        </div>
      </div>

      <div class="info-pills">
        <div class="item">Mantenimiento sin costo: ${t(a.garantia.mantenimientoMeses)} meses</div>
        <div class="item">Sucursal: ${t(a.company.sucursal)}</div>
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
  `}async function g(a,e){const{data:i}=await a.get(`ordenes/${e}`);return i}async function b(a){try{const{data:e}=await a.get("cogs/2");return Number(e?.value||e?.valor||e||0)}catch{return 0}}function $(a){const e=Array.from(a?.contentDocument?.images||[]);return e.length?Promise.all(e.map(i=>i.complete?Promise.resolve():new Promise(s=>{i.onload=()=>s(),i.onerror=()=>s()}))):Promise.resolve()}function m(a){const e=document.createElement("div");e.className="print-root",e.innerHTML=a;const i=new h;return new Promise(s=>{i.print(e,[v],[],async({iframe:o,launchPrint:l})=>{await $(o),setTimeout(()=>{l(),s()},120)})})}async function Y(a,e,i=null){const[s,o]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(a,e),b(a)]),l=p(s,o,a);return m(`
    <div class="order-double-sheet">
      ${c(l,"top")}
      <div class="half-separator"></div>
      ${c(l,"bottom")}
    </div>
  `)}async function A(a,e,i=null){const[s,o]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(a,e),b(a)]),l=p(s,o,a);return m(w(l))}export{Y as a,A as p};
