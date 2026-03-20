import{h as d}from"./moment-C5S46NFB.js";import{P as v}from"./index-C5E5KQtJ.js";const h={nombre:"JOYERIA ROSARIO",sucursal:"ORURO",direccion:"DIR. ADOLFO MIER, POTOSI Y PAGADOR (LADO PALACE HOTEL)",cel:"73800584",telefono:"52-55713"},f=`
  @page { size: letter portrait; margin: 10mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; }
  .print-root { width: 100%; color: #111; }
  .sheet {
    border: 2px solid #d50000;
    border-radius: 14px;
    padding: 10px;
    margin-bottom: 10px;
    page-break-inside: avoid;
  }
  .page-break { page-break-after: always; }
  .head { width: 100%; border-collapse: collapse; }
  .head td { vertical-align: top; }
  .center { text-align: center; }
  .right { text-align: right; }
  .brand { color: #d50000; }
  .title { font-size: 21px; font-weight: 800; letter-spacing: .3px; }
  .sub { font-size: 11px; line-height: 1.25; }
  .small { font-size: 10px; }
  .medium { font-size: 12px; }
  .large { font-size: 14px; }
  .bold { font-weight: 700; }
  .logo-box { width: 148px; text-align: center; }
  .logo-box img { max-width: 62px; max-height: 62px; display: block; margin: 0 auto 4px; object-fit: contain; }
  .side-image {
    width: 92px;
    height: 92px;
    border: 1.5px solid #d50000;
    border-radius: 10px;
    object-fit: cover;
    display: inline-block;
  }
  .fallback-image {
    width: 64px;
    height: 64px;
    object-fit: contain;
    display: inline-block;
  }
  .badge {
    display: inline-block;
    min-width: 112px;
    text-align: center;
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.2;
    margin-bottom: 6px;
  }
  .grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6px;
    margin-top: 6px;
  }
  .cell {
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 10px 10px 8px;
    position: relative;
  }
  .label {
    position: absolute;
    top: -8px;
    left: 12px;
    background: #fff;
    padding: 0 6px;
    color: #d50000;
    font-size: 10px;
    font-weight: 700;
  }
  .note-text, .justify { text-align: justify; line-height: 1.35; }
  .info-pills {
    display: table;
    width: 100%;
    border-spacing: 6px 0;
    margin-top: 6px;
  }
  .info-pills .item {
    display: table-cell;
    width: 50%;
    border: 1.7px solid #d50000;
    border-radius: 12px;
    padding: 8px;
    text-align: center;
    font-size: 11px;
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
  .dot { border-top: 1px dashed #bbb; margin: 8px 0; }
  .signature-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  .signature-table td { width: 50%; text-align: center; font-size: 10px; }
  .signature-line { border-top: 1px solid #333; width: 75%; margin: 20px auto 4px; }
  .muted { color: #666; }
  .mt-6 { margin-top: 6px; }
`;function e(a){return String(a??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function o(a){return Number(a||0).toFixed(2)}function x(a,t="DD/MM/YYYY"){return a?d(a).format(t):"—"}function y(a){return a?.cliente?.cellphone||a?.celular||"N/A"}function w(a){const t=a?.defaults?.baseURL||window.location.origin,i=new URL(t,window.location.origin);return i.pathname=i.pathname.replace(/\/api\/?$/,"/"),i.toString().replace(/\/$/,"")}function r(a,t){return t?`${a}/images/${t}`:""}function p(a,t,i){const s=w(i),n=d(a?.fecha_creacion||new Date),l=d();return{company:h,logo:r(s,"logo.png"),fallbackRings:r(s,"rings.png"),photo:r(s,a?.foto_modelo),numero:a?.numero||"",cliente:a?.cliente?.name||"N/A",telefono:y(a),detalle:[a?.detalle||"—",`Peso: ${a?.peso||0} gr.`,`Oro: ${a?.kilates18||"—"}`].join(" / "),costoTotal:o(a?.costo_total),adelanto:o(Number(a?.adelanto||0)+Number(a?.totalPagos||0)),saldo:o(a?.saldo),entrega:a?.fecha_entrega?x(a.fecha_entrega):"—",nota:a?.nota||"Ningun trabajo sera entregado sin la presente orden. Importante en caso de no recojo se espera un maximo de 90 dias antes de proceder a la fundicion de la joya",garantia:{codigo:a?.numero||"-",fecha:l.format("DD/MM/YYYY"),dia:l.format("DD"),mes:l.format("MM"),ano:l.format("YYYY"),cliente:a?.cliente?.name||"N/A",tipo:"Joya",periodo:"1 ano",detalle:a?.detalle||"—",mantenimientoMeses:12,precioOro:o(t)},fechaTrabajo:{hoy:l.format("DD/MM/YYYY"),dia:n.format("DD"),mes:n.format("MM"),ano:n.format("YYYY")}}}function c(a){const t=a.photo?`<img class="side-image" src="${e(a.photo)}" alt="Foto de modelo">`:`<img class="fallback-image" src="${e(a.fallbackRings)}" alt="Rings">`;return`
    <div class="sheet">
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
            <div style="height:16px"></div>
            <div class="brand title">ORDEN DE TRABAJO</div>
            <div class="sub">
              ${e(a.company.direccion)}<br>
              CEL. ${e(a.company.cel)} TELF. ${e(a.company.telefono)}<br>
              ${e(a.company.sucursal)} - Bolivia
            </div>
          </td>
          <td class="right" style="width:170px">
            <table style="width:100%">
              <tr>
                <td class="right">${t}</td>
                <td class="right" style="width:88px">
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
          <td class="cell" style="width:50%">
            <span class="label">El Señor:</span>
            <div class="large center">${e(String(a.cliente).toUpperCase())}</div>
          </td>
          <td class="cell" style="width:50%">
            <span class="label">Teléfono:</span>
            <div class="large center">${e(a.telefono)}</div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Trabajo de Joya:</span>
        <div class="large">${e(a.detalle)}</div>
      </div>

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

      <div class="cell mt-6">
        <span class="label">Fecha de Entrega:</span>
        <div class="center large bold">${e(a.entrega)}</div>
      </div>

      <div class="cell mt-6">
        <span class="label">NOTA</span>
        <div class="small note-text">${e(a.nota)}</div>
      </div>

      <div class="dot"></div>
    </div>
  `}function $(a){return`
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
            <div class="small muted">Cobertura por defectos de fabricación según condiciones indicadas</div>
          </td>
          <td class="right" style="width:170px">
            <div class="badge">
              <b>Bs.</b> ${e(a.garantia.precioOro)}<br>
              <span class="small muted">${e(a.garantia.fecha)}</span>
            </div>
            <div class="badge">Código: ${e(a.garantia.codigo)}</div>
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
              Día <span class="date-box">${e(a.garantia.dia)}</span>
              Mes <span class="date-box">${e(a.garantia.mes)}</span>
              Año <span class="date-box">${e(a.garantia.ano)}</span>
            </div>
          </td>
        </tr>
      </table>

      <div class="cell mt-6">
        <span class="label">Detalle de la pieza / trabajo</span>
        <div class="medium">${e(a.garantia.detalle)}</div>
        <div class="small muted mt-6">
          <b>Tipo:</b> ${e(a.garantia.tipo)}
          &nbsp;&nbsp;•&nbsp;&nbsp;
          <b>Periodo:</b> ${e(a.garantia.periodo)}
        </div>
      </div>

      <div class="info-pills">
        <div class="item">Mantenimiento sin costo: ${e(a.garantia.mantenimientoMeses)} meses</div>
        <div class="item">Sucursal: ${e(a.company.sucursal)}</div>
      </div>

      <div class="cell mt-6">
        <span class="label">Condiciones de Garantía</span>
        <div class="small justify">
          La garantía cubre <b>defectos de fabricación</b> del metal y soldaduras durante el periodo indicado.
          No cubre golpes, rayones, deformaciones por uso, exposición a químicos, humedad o temperaturas extremas,
          pérdida de piedras por impacto ni intervenciones de terceros. Es indispensable presentar este documento
          para hacer válida la garantía.
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
  `}async function g(a,t){const{data:i}=await a.get(`ordenes/${t}`);return i}async function b(a){try{const{data:t}=await a.get("cogs/2");return Number(t?.value||t?.valor||t||0)}catch{return 0}}function O(a){const t=Array.from(a?.contentDocument?.images||[]);return t.length?Promise.all(t.map(i=>i.complete?Promise.resolve():new Promise(s=>{i.onload=()=>s(),i.onerror=()=>s()}))):Promise.resolve()}function m(a){const t=document.createElement("div");t.className="print-root",t.innerHTML=a;const i=new v;return new Promise(s=>{i.print(t,[f],[],async({iframe:n,launchPrint:l})=>{await O(n),setTimeout(()=>{l(),s()},120)})})}async function A(a,t,i=null){const[s,n]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(a,t),b(a)]),l=p(s,n,a),u=`
    ${c(l)}
    <div class="page-break"></div>
    ${c(l)}
  `;return m(u)}async function D(a,t,i=null){const[s,n]=await Promise.all([i?.cliente?.cellphone?Promise.resolve(i):g(a,t),b(a)]),l=p(s,n,a);return m($(l))}export{A as a,D as p};
