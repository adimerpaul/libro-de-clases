import{t as e}from"./printd-CI3YqDZj.js";var t=e(),n={nombre:`CLÍNICA URME`,direccion:`Calle Cochabamba entre Soria Galvarro y 6 de Octubre`,celular:`70431083`,pie:[`"CLÍNICA URME" Al servicio de Oruro, Atención de Emergencias`,`las 24 horas los 365 días del año.`,`Atención en todas las Especialidades`]},r=`
  @page { size: 5.5in 8.5in; margin: 10mm; }
  body { margin: 0; }
  .proforma { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; line-height: 1.35; position: relative; }
  .titulo-doc { display: inline-block; border: 1.5px solid #000; padding: 2px 10px; font-weight: bold; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; }
  td { vertical-align: top; }
  .head { margin-top: 8px; }
  .logo { width: 90px; }
  .bold { font-weight: bold; }
  .center { text-align: center; }
  .right { text-align: right; }
  .dashed { border: none; border-top: 1.5px dashed #000; margin: 6px 0; }
  .detalle-title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13px; margin: 8px 0 6px; }
  .items th { text-align: left; border-bottom: 1.5px solid #000; padding: 2px 4px; }
  .items td { padding: 2px 4px; }
  .items th.precio, .items td.precio { border-left: 1.5px solid #000; }
  .totales td { font-weight: bold; padding: 2px 4px; }
  .totales .monto { border-top: 1.5px solid #000; }
  .son { text-align: center; font-weight: bold; margin-top: 6px; }
  .obs { margin-top: 6px; }
  .pie { text-align: center; font-size: 10px; margin-top: 16px; }
  .anulado { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-25deg);
             font-size: 52px; font-weight: bold; color: rgba(200, 0, 0, 0.35); letter-spacing: 6px; }
`;function i(e){return String(e??``).replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`)}function a(e){return Number(e||0).toFixed(2).replace(`.`,`,`)}function o(e){let t=Number(e||0);return Number.isInteger(t)?String(t):t.toFixed(2).replace(`.`,`,`)}function s(e){let t=Math.floor(Math.abs(e)),n=Math.round((Math.abs(e)-t)*100);return`${c(t).toUpperCase()} CON ${String(n).padStart(2,`0`)}/100 Bs.`}function c(e){if(e===0)return`cero`;let t=[``,`uno`,`dos`,`tres`,`cuatro`,`cinco`,`seis`,`siete`,`ocho`,`nueve`,`diez`,`once`,`doce`,`trece`,`catorce`,`quince`,`dieciséis`,`diecisiete`,`dieciocho`,`diecinueve`,`veinte`],n=[``,`veintiuno`,`veintidós`,`veintitrés`,`veinticuatro`,`veinticinco`,`veintiséis`,`veintisiete`,`veintiocho`,`veintinueve`],r=[``,``,``,`treinta`,`cuarenta`,`cincuenta`,`sesenta`,`setenta`,`ochenta`,`noventa`],i=[``,`ciento`,`doscientos`,`trescientos`,`cuatrocientos`,`quinientos`,`seiscientos`,`setecientos`,`ochocientos`,`novecientos`];if(e<=20)return t[e];if(e<30)return n[e-20];if(e<100){let n=Math.floor(e/10),i=e%10;return i===0?r[n]:`${r[n]} y ${t[i]}`}if(e===100)return`cien`;if(e<1e3){let t=Math.floor(e/100),n=e%100;return i[t]+(n>0?` `+c(n):``)}if(e<2e3){let t=e%1e3;return`mil`+(t>0?` `+c(t):``)}if(e<1e6){let t=Math.floor(e/1e3),n=e%1e3;return c(t)+` mil`+(n>0?` `+c(n):``)}if(e<2e6){let t=e%1e6;return`un millón`+(t>0?` `+c(t):``)}if(e<1e9){let t=Math.floor(e/1e6),n=e%1e6;return c(t)+` millones`+(n>0?` `+c(n):``)}return String(e)}function l(e){let t=window.location.origin+`/logo.png`,r=(e.fecha_hora||``).replace(`T`,` `).slice(0,19)||`—`,c=e.tipo_movimiento===`EGRESO`,l=e.paciente?.nombre_completo||e.cliente||(c?`GASTO DE CAJA`:`SIN NOMBRE`),u=e.detalles||[],d=u.length?u.map(e=>`
        <tr>
          <td>${i((e.producto?.codigo?e.producto.codigo+`-`:``)+(e.nombre||e.producto?.nombre||`—`))}</td>
          <td class="right">${o(e.cantidad)}</td>
          <td class="right precio">${a(e.precio)}</td>
          <td class="right">${a(e.total)}</td>
        </tr>`).join(``):`<tr><td colspan="4" class="center">Sin detalles</td></tr>`;return`
    <div class="proforma">
      ${e.estado===`ANULADO`?`<div class="anulado">ANULADO</div>`:``}
      <div class="titulo-doc">${c?`COMPROBANTE DE GASTO`:`PROFORMA DE PAGO`}</div>

      <table class="head">
        <tr>
          <td style="width:30%" class="center">
            <img class="logo" src="${t}" alt="Logo" onerror="this.style.display='none'">
          </td>
          <td style="width:70%">
            <span class="bold">${i(n.nombre)}</span><br>
            <span class="bold">Dirección:</span> ${i(n.direccion)}<br>
            <span class="bold">Celular:</span> ${i(n.celular)}<br>
            <span class="bold">${c?`GASTO`:`VENTA`} N° ${String(e.id).padStart(6,`0`)}</span>
          </td>
        </tr>
      </table>

      <hr class="dashed">

      <table>
        <tr>
          <td style="width:65%">
            <span class="bold">Cajero:</span> ${i(e.user?.name||`—`)}<br>
            <span class="bold">Fecha:</span> ${i(r)}<br>
            <span class="bold">Cliente:</span> ${i(l)}<br>
            ${e.doctor?`<span class="bold">Doctor:</span> ${i(e.doctor)}<br>`:``}
            <span class="bold">Pago:</span> ${i(e.tipo_pago||`—`)}
          </td>
          <td style="width:35%">
            <span class="bold">CI/NIT:</span> ${i(e.paciente?.ci||`0`)}<br>
            <span class="bold">Estado:</span> ${i(e.estado||`—`)}
          </td>
        </tr>
      </table>

      <div class="detalle-title">${c?`DETALLE DEL GASTO`:`SERVICIOS SOLICITADOS`}</div>

      <table class="items">
        <thead>
          <tr>
            <th style="width:56%">Descripción</th>
            <th style="width:12%" class="right">Cant.</th>
            <th style="width:16%" class="right precio">Precio</th>
            <th style="width:16%" class="right">Total</th>
          </tr>
        </thead>
        <tbody>${d}</tbody>
      </table>

      <table class="totales">
        <tr>
          <td style="width:68%" class="right">TOTAL Bs. :</td>
          <td style="width:32%" class="right monto">${a(e.total)}</td>
        </tr>
        ${c?``:`
        <tr>
          <td class="right">PAGO Bs. :</td>
          <td class="right">${a(e.pago)}</td>
        </tr>
        <tr>
          <td class="right">CAMBIO Bs. :</td>
          <td class="right">${a(e.cambio)}</td>
        </tr>`}
      </table>

      <div class="son">SON: ${i(s(Number(e.total||0)))}</div>

      ${e.comentario?`<div class="obs"><span class="bold">Obs.:</span> ${i(e.comentario)}</div>`:``}

      <hr class="dashed" style="margin-top:16px">
      <div class="pie">${n.pie.map(i).join(`<br>`)}</div>
    </div>
  `}function u(e){let n=document.createElement(`div`);n.innerHTML=l(e),new t.Printd().print(n,[r],[],({launchPrint:e})=>e())}export{u as t};