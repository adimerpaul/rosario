import{h as c}from"./moment-C5S46NFB.js";import{P as f}from"./index-C5E5KQtJ.js";const u=`
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
`;function e(t){return String(t??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function o(t){return Number(t||0).toFixed(2)}async function p(t,d){const{data:i}=await t.get(`prestamos/${d}`);return i}async function y(t,d){const{data:i}=await t.get(`prestamos/${d}/pagos`);return Array.isArray(i)?i:[]}async function g(t){try{const{data:d}=await t.get("cogs/4");return Number(d?.value||d?.valor||d||6.96)||6.96}catch{return 6.96}}function v(t){const d=document.createElement("div");d.innerHTML=t;const i=new f;return new Promise(s=>{i.print(d,[u],[],({launchPrint:l})=>{setTimeout(()=>{l(),s()},80)})})}function m(t,d){const i=Number(t?.valor_prestado||0),s=Number(t?.interes||0),l=Number(t?.almacen||0),a=i*(s+l)/100,n=Number(t?.valor_total||0),r=Number(t?.peso||0),b=Number(t?.merma||0),x=Number(t?.peso_neto||r-b),h=t?.fecha_limite?c(t.fecha_limite):c(t?.fecha_creacion||new Date);return{numero:t?.numero||"—",cliente:t?.cliente?.name||"—",ci:t?.cliente?.ci||"—",celular:t?.celular||t?.cliente?.cellphone||"—",lugar:"Oruro",fechaCreacion:t?.fecha_creacion?c(t.fecha_creacion).format("DD/MM/YYYY"):"—",fechaCambio:t?.fecha_creacion?c(t.fecha_creacion).format("D [de] MMMM [de] YYYY"):c().format("D [de] MMMM [de] YYYY"),vencimiento:h.add(1,"month").format("DD/MM/YYYY"),monedaLarga:"Dólares",monedaCorta:"SUS",valorBienesUsd:o(n/d),capitalUsd:o(i/d),cargoMensualUsd:o(a/d),cargoMensualBs:o(a),interesMensual:s.toFixed(2).replace(/\.00$/,""),almacenMensual:l.toFixed(2).replace(/\.00$/,""),interesMonto30:o(i*s/100),almacenMonto30:o(i*l/100),plazoDias:30,detalle:t?.detalle||"—",pesoTotal:o(r),merma:o(b),pesoOro:o(x),agencia:"JOYERIA ROSARIO",direccion:"Adolfo Mier entre Potosi y pagador (Lado palace Hotel)",usuario:String(t?.user?.username||t?.user?.name||"USUARIO").toUpperCase(),tipoCambio:o(d),montoPrestadoBs:o(i),montoPrestadoUsd:o(i/d),referencia:t?.numero||`PR-${t?.id||""}`,docSerie:"PR",docNro:String(t?.id||"").padStart(8,"0")}}function w(t){return`
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
            <div class="badge">Nro: ${e(t.numero)}</div>
          </td>
        </tr>
      </table>

      <table class="grid" style="margin-top:8px;">
        <tr>
          <td class="box" style="width:42%;">
            <div class="label">Cliente</div>
            <div class="md" style="font-weight:700;">${e(t.cliente)}</div>
            <div class="sm">CI: ${e(t.ci)}</div>
            <div class="sm">Cel: ${e(t.celular)}</div>
          </td>
          <td class="box" style="width:18%;">
            <div class="label">Lugar</div>
            <div class="md">${e(t.lugar)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Fecha</div>
            <div class="md">${e(t.fechaCreacion)}</div>
          </td>
          <td class="box" style="width:20%;">
            <div class="label">Vencimiento</div>
            <div class="md">${e(t.vencimiento)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:25%;">
            <div class="label">Moneda</div>
            <div class="md">${e(t.monedaLarga)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Valor bienes</div>
            <div class="md">${e(t.valorBienesUsd)} ${e(t.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Capital solicitado</div>
            <div class="md">${e(t.capitalUsd)} ${e(t.monedaCorta)}</div>
          </td>
          <td class="box" style="width:25%;">
            <div class="label">Cargo mensual</div>
            <div class="md">${e(t.cargoMensualUsd)} ${e(t.monedaCorta)}</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Interes</div>
            <div class="md">${e(t.interesMensual)} %</div>
<!--            <div class="xs muted">30 dias: ${e(t.interesMonto30)} ${e(t.monedaCorta)}</div>-->
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Almacen</div>
            <div class="md">${e(t.almacenMensual)} %</div>
<!--            <div class="xs muted">30 dias: ${e(t.almacenMonto30)} ${e(t.monedaCorta)}</div>-->
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Plazo</div>
            <div class="md">${e(t.plazoDias)} dias</div>
          </td>
        </tr>
      </table>

      <div class="box" style="margin-top:6px;">
        <div class="label">Detalle de joyas</div>
        <div class="md">${e(t.detalle)}</div>
      </div>

      <table class="grid" style="margin-top:6px;">
        <tr>
          <td class="box" style="width:33%;">
            <div class="label">Peso total</div>
            <div class="md">${e(t.pesoTotal)} gr</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Peso merma/piedras</div>
            <div class="md">${e(t.merma)} gr</div>
          </td>
          <td class="box" style="width:34%;">
            <div class="label">Peso en oro</div>
            <div class="md">${e(t.pesoOro)} gr</div>
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
  `}function $(t){return`
    <div class="sheet" style="border-color:#333;">
      <div class="title center" style="margin-bottom:8px;">COMPROBANTE DE CAMBIO DE MONEDA</div>
      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Agencia</div>
            <div>${e(t.agencia)}</div>
          </td>
          <td class="box" style="width:35%">
            <div class="label">Fecha</div>
            <div>${e(t.fechaCambio)}</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Direccion</div>
            <div>${e(t.direccion)}</div>
          </td>
          <td class="box">
            <div class="label">Usuario</div>
            <div style="font-weight:700;">${e(t.usuario)}</div>
          </td>
        </tr>
        <tr>
          <td class="box" colspan="2">
            <div class="label">Cliente</div>
            <div style="font-weight:700;">${e(t.cliente)}</div>
          </td>
        </tr>
      </table>

      <div class="sep"></div>

      <div class="box">
        <div class="label">TIPO CAMBIO OFICIAL</div>
        <div class="muted">Tipo de cambio aplicado: <span class="mono">${e(t.tipoCambio)} Bs/$us</span></div>
      </div>

      <table class="grid">
        <tr>
          <td class="box">
            <div class="label">Peso Total (g)</div>
            <div style="font-weight:700;">${e(t.pesoTotal)} g</div>
          </td>
          <td class="box">
            <div class="label">Merma (g)</div>
            <div style="font-weight:700;">${e(t.merma)} g</div>
          </td>
          <td class="box">
            <div class="label">Peso en Oro (g)</div>
            <div style="font-weight:700;">${e(t.pesoOro)} g</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Monto Prestado ($us)</div>
            <div style="font-weight:700;">${e(t.montoPrestadoUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Monto Bolivianos (Bs)</div>
            <div style="font-weight:700;">${e(t.montoPrestadoBs)} Bs</div>
          </td>
        </tr>
        <tr>
          <td class="box">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${e(t.cargoMensualUsd)} $us</div>
          </td>
          <td class="box" colspan="2">
            <div class="label">Interes</div>
            <div style="font-weight:700;">${e(t.cargoMensualBs)} Bs</div>
          </td>
        </tr>
      </table>

      <table class="grid">
        <tr>
          <td class="box" style="width:60%">
            <div class="label">Concepto</div>
            <div>Cambio Dolares</div>
            <div class="muted">Cambio Dolares (asociado a prestamo ${e(t.referencia)})</div>
          </td>
          <td class="box" style="width:40%">
            <div class="label">Doc. Nro.</div>
            <div class="mono">${e(t.docSerie)} ${e(t.docNro)}</div>
          </td>
        </tr>
      </table>

      <div class="box">
        <div class="label">Referencia de Prestamo</div>
        <div class="mono" style="font-weight:700;">${e(t.referencia)}</div>
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
  `}function M(t,d){const i=String(t?.cliente?.name||"—").toUpperCase(),s=Number(t?.peso||0).toFixed(0),l=Number(d?.monto||0).toFixed(0),a=t?.fecha_limite?c(t.fecha_limite).format("D/M/YYYY"):"—",n=t?.fecha_cancelacion?c(t.fecha_cancelacion).format("D/M/YYYY"):"—",r=c().format("DD/MM/YYYY HH:mm");return{empresa:"Joyeria Rosario",direccion:"Adolfo Mier entre Potosi y Pagador",ciudadPais:"Oruro - Bolivia",celular:"73800584",pagoId:d?.id||"",clienteNombre:i,peso:s,monto:l,fechaCancelado:a,fechaVencimiento:n,usuario:d?.user?.name||"",emitido:r}}function P(t,d){const i=(d||[]).map(s=>({id:s?.id||"",fecha:s?.fecha?c(s.fecha).format("DD/MM/YYYY"):"—",tipo:s?.tipo_pago||"—",monto:Number(s?.monto||0),metodo:s?.metodo||"—",usuario:s?.user?.name||"—",estado:s?.estado||"—"}));return{empresa:"Joyeria Rosario",direccion:"Adolfo Mier entre Potosi y Pagador",ciudadPais:"Oruro - Bolivia",celular:"73800584",numero:t?.numero||`PR-${t?.id||""}`,cliente:t?.cliente?.name||"—",fechaCreacion:t?.fecha_creacion?c(t.fecha_creacion).format("DD/MM/YYYY"):"—",mesCancelado:t?.fecha_limite?c(t.fecha_limite).format("DD/MM/YYYY"):"—",mesVencimiento:t?.fecha_cancelacion?c(t.fecha_cancelacion).format("DD/MM/YYYY"):"—",pagos:i}}async function D(t,d){const i=M(t,d),s=i.usuario?` por ${e(i.usuario)}`:"";return v(`
    <div class="sheet">
      <table style="width:100%; border-collapse:collapse;">
        <tr>
          <td style="width:72px; vertical-align:top;"></td>
          <td class="center">
            <div class="title" style="color:#991b1b;">${e(i.empresa)}</div>
            <div class="sm">${e(i.direccion)}</div>
            <div class="sm">${e(i.ciudadPais)}</div>
            <div class="sm">Cel: ${e(i.celular)}</div>
            <div class="md" style="margin-top:4px; font-weight:700;">COMPROBANTE DE PAGO</div>
          </td>
          <td class="right" style="width:120px; vertical-align:top;">
            <div class="badge">Pago #${e(i.pagoId)}</div>
          </td>
        </tr>
      </table>

      <div style="border:1.5px solid #6aa84f; border-radius:10px; padding:10px 12px; margin-top:8px;">
        <div class="label center" style="display:block; margin-bottom:8px;">DETALLE DEL PAGO</div>
        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <td style="width:42%; padding:5px 0; font-size:12px; font-weight:700; color:#444;">Nombre:</td>
            <td style="padding:5px 0; font-size:13px; font-weight:700; color:#111;">${e(i.clienteNombre)}</td>
          </tr>
          <tr>
            <td style="padding:5px 0; font-size:12px; font-weight:700; color:#444;">Peso:</td>
            <td style="padding:5px 0; font-size:13px; font-weight:700; color:#111;">${e(i.peso)} GR</td>
          </tr>
          <tr>
            <td style="padding:5px 0; font-size:12px; font-weight:700; color:#444;">Monto:</td>
            <td style="padding:5px 0; font-size:13px; font-weight:700; color:#111;">${e(i.monto)} Bs</td>
          </tr>
        </table>

        <div style="border-top:1px dashed #d9d9d9; margin:8px 0;"></div>

        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <td style="width:42%; padding:5px 0; font-size:12px; font-weight:700; color:#444;">Mes cancelado:</td>
            <td style="padding:5px 0; font-size:13px; font-weight:700; color:#111;">${e(i.fechaCancelado)}</td>
          </tr>
          <tr>
            <td style="padding:5px 0; font-size:12px; font-weight:700; color:#444;">Mes vencimiento:</td>
            <td style="padding:5px 0; font-size:13px; font-weight:700; color:#111;">${e(i.fechaVencimiento)}</td>
          </tr>
        </table>
      </div>

      <div class="xs muted center" style="margin-top:8px;">
        Comprobante emitido el ${e(i.emitido)}${s}
      </div>
    </div>
  `)}async function O(t,d,i=null){const[s,l]=await Promise.all([i?.cliente?Promise.resolve(i):p(t,d),y(t,d)]),a=P(s,l),n=a.pagos.map(r=>`
    <tr>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;">${e(String(r.id))}</td>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;">${e(r.fecha)}</td>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;">${e(r.tipo)}</td>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;" class="right">${e(o(r.monto))} Bs</td>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;">${e(r.metodo)}</td>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;">${e(r.usuario)}</td>
      <td style="padding:7px 8px; border-bottom:1px solid #ddd;">${e(r.estado)}</td>
    </tr>
  `).join("");return v(`
    <div class="sheet" style="border-color:#1f2937;">
      <div class="center">
        <div class="title" style="color:#111827;">DETALLE DE PAGOS DEL PRESTAMO</div>
        <div class="sm">${e(a.empresa)} · ${e(a.direccion)}</div>
        <div class="sm">${e(a.ciudadPais)} · Cel: ${e(a.celular)}</div>
      </div>

      <table class="grid" style="margin-top:8px;">
        <tr>
          <td class="box" style="width:34%;">
            <div class="label">Prestamo</div>
            <div class="md" style="font-weight:700;">${e(a.numero)}</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Cliente</div>
            <div class="md" style="font-weight:700;">${e(a.cliente)}</div>
          </td>
          <td class="box" style="width:33%;">
            <div class="label">Fecha de creacion</div>
            <div class="md">${e(a.fechaCreacion)}</div>
          </td>
        </tr>
      </table>

      <div class="box" style="margin-top:8px;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="background:#f3f4f6;">
              <th style="text-align:left; padding:8px;">#</th>
              <th style="text-align:left; padding:8px;">Fecha</th>
              <th style="text-align:left; padding:8px;">Tipo</th>
              <th style="text-align:right; padding:8px;">Monto</th>
              <th style="text-align:left; padding:8px;">Metodo</th>
              <th style="text-align:left; padding:8px;">Usuario</th>
              <th style="text-align:left; padding:8px;">Estado</th>
            </tr>
          </thead>
          <tbody>
            ${n||`
              <tr>
                <td colspan="7" style="padding:12px; text-align:center; color:#666;">Sin pagos registrados</td>
              </tr>
            `}
          </tbody>
        </table>
      </div>

      <table class="grid" style="margin-top:8px;">
        <tr>
          <td class="box" style="width:50%;">
            <div class="label">Mes cancelado</div>
            <div class="md" style="font-weight:700;">${e(a.mesCancelado)}</div>
          </td>
          <td class="box" style="width:50%;">
            <div class="label">Mes vencimiento</div>
            <div class="md" style="font-weight:700;">${e(a.mesVencimiento)}</div>
          </td>
        </tr>
      </table>
    </div>
  `)}async function N(t,d,i=null){const[s,l]=await Promise.all([i?.cliente?Promise.resolve(i):p(t,d),g(t)]),a=m(s,l);return v(w(a))}async function A(t,d,i=null){const[s,l]=await Promise.all([i?.cliente?Promise.resolve(i):p(t,d),g(t)]),a=m(s,l);return v($(a))}export{A as a,N as b,D as c,O as p};
