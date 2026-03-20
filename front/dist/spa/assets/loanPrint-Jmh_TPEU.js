import{h as r}from"./moment-C5S46NFB.js";import{P as h}from"./index-C5E5KQtJ.js";const f=`
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
`;function i(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function a(e){return Number(e||0).toFixed(2)}async function v(e,t){const{data:s}=await e.get(`prestamos/${t}`);return s}async function b(e){try{const{data:t}=await e.get("cogs/4");return Number(t?.value||t?.valor||t||6.96)||6.96}catch{return 6.96}}function p(e){const t=document.createElement("div");t.innerHTML=e;const s=new h;return new Promise(d=>{s.print(t,[f],[],({launchPrint:l})=>{setTimeout(()=>{l(),d()},80)})})}function g(e,t){const s=Number(e?.valor_prestado||0),d=Number(e?.interes||0),l=Number(e?.almacen||0),o=s*(d+l)/100,m=Number(e?.valor_total||0),c=Number(e?.peso||0),n=Number(e?.merma||0),u=Number(e?.peso_neto||c-n),x=e?.fecha_limite?r(e.fecha_limite):r(e?.fecha_creacion||new Date);return{numero:e?.numero||"—",cliente:e?.cliente?.name||"—",ci:e?.cliente?.ci||"—",celular:e?.celular||e?.cliente?.cellphone||"—",lugar:"Oruro",fechaCreacion:e?.fecha_creacion?r(e.fecha_creacion).format("DD/MM/YYYY"):"—",fechaCambio:e?.fecha_creacion?r(e.fecha_creacion).format("D [de] MMMM [de] YYYY"):r().format("D [de] MMMM [de] YYYY"),vencimiento:x.add(1,"month").format("DD/MM/YYYY"),monedaLarga:"Dólares",monedaCorta:"SUS",valorBienesUsd:a(m/t),capitalUsd:a(s/t),cargoMensualUsd:a(o/t),cargoMensualBs:a(o),interesMensual:d.toFixed(2).replace(/\.00$/,""),almacenMensual:l.toFixed(2).replace(/\.00$/,""),interesMonto30:a(s*d/100),almacenMonto30:a(s*l/100),plazoDias:30,detalle:e?.detalle||"—",pesoTotal:a(c),merma:a(n),pesoOro:a(u),agencia:"JOYERIA ROSARIO",direccion:"Adolfo Mier entre Potosi y pagador (Lado palace Hotel)",usuario:String(e?.user?.username||e?.user?.name||"USUARIO").toUpperCase(),tipoCambio:a(t),montoPrestadoBs:a(s),montoPrestadoUsd:a(s/t),referencia:e?.numero||`PR-${e?.id||""}`,docSerie:"PR",docNro:String(e?.id||"").padStart(8,"0")}}function y(e){return`
    <div class="sheet">
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
            <div class="badge">Nro: ${i(e.numero)}</div>
          </td>
        </tr>
      </table>

      <table class="grid" style="margin-top:8px;">
        <tr>
          <td class="box" style="width:42%;">
            <div class="label">Cliente</div>
            <div class="md" style="font-weight:700;">${i(e.cliente)}</div>
            <div class="sm">CI: ${i(e.ci)}</div>
            <div class="sm">Cel: ${i(e.celular)}</div>
          </td>
          <td class="box" style="width:18%;">
            <div class="label">Lugar</div>
            <div class="md">${i(e.lugar)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Fecha</div>
            <div class="md">${i(e.fechaCreacion)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Vencimiento</div>
            <div class="md">${i(e.vencimiento)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:25%;">
            <div class="label">Moneda</div>
            <div class="md">${i(e.monedaLarga)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Valor bienes</div>
            <div class="md">${i(e.valorBienesUsd)} ${i(e.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Capital solicitado</div>
            <div class="md">${i(e.capitalUsd)} ${i(e.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Cargo mensual</div>
            <div class="md">${i(e.cargoMensualUsd)} ${i(e.monedaCorta)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Interes</div>
            <div class="md">${i(e.interesMensual)} %</div>
            <div class="xs muted">30 dias: ${i(e.interesMonto30)} ${i(e.monedaCorta)}</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Almacen</div>
            <div class="md">${i(e.almacenMensual)} %</div>
            <div class="xs muted">30 dias: ${i(e.almacenMonto30)} ${i(e.monedaCorta)}</div>
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Plazo</div>
            <div class="md">${i(e.plazoDias)} dias</div>
          </td>
        </tr>
      </table>

      <div class="box" style="margin-top:6px;">
        <div class="label">Detalle de joyas</div>
        <div class="md">${i(e.detalle)}</div>
      </div>

      <table class="grid" style="margin-top:6px;">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Peso total</div>
            <div class="md">${i(e.pesoTotal)} gr</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Peso merma/piedras</div>
            <div class="md">${i(e.merma)} gr</div>
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Peso en oro</div>
            <div class="md">${i(e.pesoOro)} gr</div>
          </td>
        </tr>
      </table>

      <div class="xs muted" style="margin-top:8px;">
        El cliente declara la veracidad de la informacion proporcionada y el origen licito de los bienes dejados en prenda.
        P.B.: peso bruto.
      </div>

      <table style="width:100%; margin-top:18px;">
        <tr>
          <td style="width:50%;">
            <div class="sign-line"></div>
            <div class="xs center">Firma del cliente</div>
            <div class="xs center">${i(e.cliente)}</div>
          </td>
          <td style="width:50%;">
            <div class="sign-line"></div>
            <div class="xs center">Firma joyeria</div>
          </td>
        </tr>
      </table>
    </div>
  `}function w(e){return`
    <div class="sheet" style="border-color:#333;">
      <div class="title center" style="margin-bottom:8px;">COMPROBANTE DE CAMBIO DE MONEDA</div>
      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Agencia</div>
            <div>${i(e.agencia)}</div>
          </td>
          <td class="box" style="width:35%">
            <div class="label">Fecha</div>
            <div>${i(e.fechaCambio)}</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Direccion</div>
            <div>${i(e.direccion)}</div>
          </td>
          <td class="box">
            <div class="label">Usuario</div>
            <div style="font-weight:700;">${i(e.usuario)}</div>
          </td>
        </tr>
        <tr>
          <td class="box" colspan="2">
            <div class="label">Cliente</div>
            <div style="font-weight:700;">${i(e.cliente)}</div>
          </td>
        </tr>
      </table>

      <div class="sep"></div>

      <div class="box">
        <div class="label">TIPO CAMBIO OFICIAL</div>
        <div class="muted">Tipo de cambio aplicado: <span class="mono">${i(e.tipoCambio)} Bs/$us</span></div>
      </div>

      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Peso Total (g)</div>
            <div style="font-weight:700;">${i(e.pesoTotal)} g</div>
          </td>
          <td class="box">
            <div class="label">Merma (g)</div>
            <div style="font-weight:700;">${i(e.merma)} g</div>
          </td>
          <td class="box">
            <div class="label">Peso en Oro (g)</div>
            <div style="font-weight:700;">${i(e.pesoOro)} g</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Monto Prestado ($us)</div>
            <div style="font-weight:700;">${i(e.montoPrestadoUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Monto Bolivianos (Bs)</div>
            <div style="font-weight:700;">${i(e.montoPrestadoBs)} Bs</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${i(e.cargoMensualUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${i(e.cargoMensualBs)} Bs</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:60%">
            <div class="label">Concepto</div>
            <div>Cambio Dolares</div>
            <div class="muted">Cambio Dolares (asociado a prestamo ${i(e.referencia)})</div>
          </td>
          <td class="box" style="width:40%">
            <div class="label">Doc. Nro.</div>
            <div class="mono">${i(e.docSerie)} ${i(e.docNro)}</div>
          </td>
        </tr>
      </table>

      <div class="box">
        <div class="label">Referencia de Prestamo</div>
        <div class="mono" style="font-weight:700;">${i(e.referencia)}</div>
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
  `}async function C(e,t,s=null){const[d,l]=await Promise.all([s?.cliente?Promise.resolve(s):v(e,t),b(e)]),o=g(d,l);return p(y(o))}async function P(e,t,s=null){const[d,l]=await Promise.all([s?.cliente?Promise.resolve(s):v(e,t),b(e)]),o=g(d,l);return p(w(o))}export{C as a,P as p};
