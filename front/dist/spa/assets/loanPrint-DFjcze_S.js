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
`;function e(i){return String(i??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function a(i){return Number(i||0).toFixed(2)}async function v(i,t){const{data:s}=await i.get(`prestamos/${t}`);return s}async function b(i){try{const{data:t}=await i.get("cogs/4");return Number(t?.value||t?.valor||t||6.96)||6.96}catch{return 6.96}}function p(i){const t=document.createElement("div");t.innerHTML=i;const s=new h;return new Promise(d=>{s.print(t,[f],[],({launchPrint:l})=>{setTimeout(()=>{l(),d()},80)})})}function g(i,t){const s=Number(i?.valor_prestado||0),d=Number(i?.interes||0),l=Number(i?.almacen||0),o=s*(d+l)/100,m=Number(i?.valor_total||0),c=Number(i?.peso||0),n=Number(i?.merma||0),u=Number(i?.peso_neto||c-n),x=i?.fecha_limite?r(i.fecha_limite):r(i?.fecha_creacion||new Date);return{numero:i?.numero||"—",cliente:i?.cliente?.name||"—",ci:i?.cliente?.ci||"—",celular:i?.celular||i?.cliente?.cellphone||"—",lugar:"Oruro",fechaCreacion:i?.fecha_creacion?r(i.fecha_creacion).format("DD/MM/YYYY"):"—",fechaCambio:i?.fecha_creacion?r(i.fecha_creacion).format("D [de] MMMM [de] YYYY"):r().format("D [de] MMMM [de] YYYY"),vencimiento:x.add(1,"month").format("DD/MM/YYYY"),monedaLarga:"Dólares",monedaCorta:"SUS",valorBienesUsd:a(m/t),capitalUsd:a(s/t),cargoMensualUsd:a(o/t),cargoMensualBs:a(o),interesMensual:d.toFixed(2).replace(/\.00$/,""),almacenMensual:l.toFixed(2).replace(/\.00$/,""),interesMonto30:a(s*d/100),almacenMonto30:a(s*l/100),plazoDias:30,detalle:i?.detalle||"—",pesoTotal:a(c),merma:a(n),pesoOro:a(u),agencia:"JOYERIA ROSARIO",direccion:"Adolfo Mier entre Potosi y pagador (Lado palace Hotel)",usuario:String(i?.user?.username||i?.user?.name||"USUARIO").toUpperCase(),tipoCambio:a(t),montoPrestadoBs:a(s),montoPrestadoUsd:a(s/t),referencia:i?.numero||`PR-${i?.id||""}`,docSerie:"PR",docNro:String(i?.id||"").padStart(8,"0")}}function y(i){return`
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
            <div class="badge">Nro: ${e(i.numero)}</div>
          </td>
        </tr>
      </table>

      <table class="grid" style="margin-top:8px;">
        <tr>
          <td class="box" style="width:42%;">
            <div class="label">Cliente</div>
            <div class="md" style="font-weight:700;">${e(i.cliente)}</div>
            <div class="sm">CI: ${e(i.ci)}</div>
            <div class="sm">Cel: ${e(i.celular)}</div>
          </td>
          <td class="box" style="width:18%;">
            <div class="label">Lugar</div>
            <div class="md">${e(i.lugar)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Fecha</div>
            <div class="md">${e(i.fechaCreacion)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Vencimiento</div>
            <div class="md">${e(i.vencimiento)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:25%;">
            <div class="label">Moneda</div>
            <div class="md">${e(i.monedaLarga)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Valor bienes</div>
            <div class="md">${e(i.valorBienesUsd)} ${e(i.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Capital solicitado</div>
            <div class="md">${e(i.capitalUsd)} ${e(i.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Cargo mensual</div>
            <div class="md">${e(i.cargoMensualUsd)} ${e(i.monedaCorta)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Interes</div>
            <div class="md">${e(i.interesMensual)} %</div>
            <div class="xs muted">30 dias: ${e(i.interesMonto30)} ${e(i.monedaCorta)}</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Almacen</div>
            <div class="md">${e(i.almacenMensual)} %</div>
            <div class="xs muted">30 dias: ${e(i.almacenMonto30)} ${e(i.monedaCorta)}</div>
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Plazo</div>
            <div class="md">${e(i.plazoDias)} dias</div>
          </td>
        </tr>
      </table>

      <div class="box" style="margin-top:6px;">
        <div class="label">Detalle de joyas</div>
        <div class="md">${e(i.detalle)}</div>
      </div>

      <table class="grid" style="margin-top:6px;">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Peso total</div>
            <div class="md">${e(i.pesoTotal)} gr</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Peso merma/piedras</div>
            <div class="md">${e(i.merma)} gr</div>
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Peso en oro</div>
            <div class="md">${e(i.pesoOro)} gr</div>
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
  `}function w(i){return`
    <div class="sheet" style="border-color:#333;">
      <div class="title center" style="margin-bottom:8px;">COMPROBANTE DE CAMBIO DE MONEDA</div>
      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Agencia</div>
            <div>${e(i.agencia)}</div>
          </td>
          <td class="box" style="width:35%">
            <div class="label">Fecha</div>
            <div>${e(i.fechaCambio)}</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Direccion</div>
            <div>${e(i.direccion)}</div>
          </td>
          <td class="box">
            <div class="label">Usuario</div>
            <div style="font-weight:700;">${e(i.usuario)}</div>
          </td>
        </tr>
        <tr>
          <td class="box" colspan="2">
            <div class="label">Cliente</div>
            <div style="font-weight:700;">${e(i.cliente)}</div>
          </td>
        </tr>
      </table>

      <div class="sep"></div>

      <div class="box">
        <div class="label">TIPO CAMBIO OFICIAL</div>
        <div class="muted">Tipo de cambio aplicado: <span class="mono">${e(i.tipoCambio)} Bs/$us</span></div>
      </div>

      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Peso Total (g)</div>
            <div style="font-weight:700;">${e(i.pesoTotal)} g</div>
          </td>
          <td class="box">
            <div class="label">Merma (g)</div>
            <div style="font-weight:700;">${e(i.merma)} g</div>
          </td>
          <td class="box">
            <div class="label">Peso en Oro (g)</div>
            <div style="font-weight:700;">${e(i.pesoOro)} g</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Monto Prestado ($us)</div>
            <div style="font-weight:700;">${e(i.montoPrestadoUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Monto Bolivianos (Bs)</div>
            <div style="font-weight:700;">${e(i.montoPrestadoBs)} Bs</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${e(i.cargoMensualUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${e(i.cargoMensualBs)} Bs</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:60%">
            <div class="label">Concepto</div>
            <div>Cambio Dolares</div>
            <div class="muted">Cambio Dolares (asociado a prestamo ${e(i.referencia)})</div>
          </td>
          <td class="box" style="width:40%">
            <div class="label">Doc. Nro.</div>
            <div class="mono">${e(i.docSerie)} ${e(i.docNro)}</div>
          </td>
        </tr>
      </table>

      <div class="box">
        <div class="label">Referencia de Prestamo</div>
        <div class="mono" style="font-weight:700;">${e(i.referencia)}</div>
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
  `}async function C(i,t,s=null){const[d,l]=await Promise.all([s?.cliente?Promise.resolve(s):v(i,t),b(i)]),o=g(d,l);return p(y(o))}async function P(i,t,s=null){const[d,l]=await Promise.all([s?.cliente?Promise.resolve(s):v(i,t),b(i)]),o=g(d,l);return p(w(o))}export{C as a,P as p};
