import { Printd } from 'printd'
import { formatBoliviaDate, formatBoliviaDateTime, formatBoliviaTime } from './dateTime'

/**
 * Voucher del cierre de caja: mismo papel y tipografía que los recibos de
 * venta y de caja (media carta, monoespaciada), para que salga por la misma
 * impresora sin cambiar la configuración.
 */

const CLINICA = {
  nombre: 'CLÍNICA URME',
  direccion: 'Calle Cochabamba entre Soria Galvarro y 6 de Octubre',
  celular: '70431083',
  pie: [
    '"CLÍNICA URME" Al servicio de Oruro, Atención de Emergencias',
    'las 24 horas los 365 días del año.',
    'Atención en todas las Especialidades',
  ],
}

const CSS = `
  @page { size: 5.5in 8.5in; margin: 10mm; }
  body { margin: 0; }
  .voucher { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; line-height: 1.35; }
  table { width: 100%; border-collapse: collapse; }
  td { vertical-align: top; }
  .head { margin-top: 8px; }
  .logo { width: 90px; }
  .bold { font-weight: bold; }
  .center { text-align: center; }
  .right { text-align: right; }
  .dashed { border: none; border-top: 1.5px dashed #000; margin: 6px 0; }
  .titulo { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13px; margin: 9px 0 7px; }
  .sub { font-weight: bold; text-decoration: underline; margin: 8px 0 3px; }
  .montos td { padding: 2px 4px; }
  .montos .etiqueta { border-bottom: 1px dotted #999; }
  .montos .valor { text-align: right; border-bottom: 1px dotted #999; white-space: nowrap; }
  .montos .fuerte td { font-weight: bold; font-size: 12px; border-top: 1.5px solid #000; border-bottom: none; }
  .items th { text-align: left; border-bottom: 1.5px solid #000; padding: 2px 4px; }
  .items td { padding: 2px 4px; border-bottom: 1px dotted #bbb; }
  .items .num { text-align: right; white-space: nowrap; }
  .son { text-align: center; font-weight: bold; margin-top: 6px; }
  .nota { font-size: 10px; margin-top: 4px; }
  .firmas { margin-top: 34px; }
  .firmas td { text-align: center; font-size: 10px; padding: 0 8px; }
  .firmas .linea { border-top: 1px solid #000; padding-top: 3px; }
  .pie { text-align: center; font-size: 10px; margin-top: 14px; }
`

function esc (v) {
  return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}

function money (v) {
  return Number(v || 0).toFixed(2).replace('.', ',')
}

/* Las fechas del API llegan en UTC: se imprimen en hora de Bolivia, la misma
   que muestra el sistema en pantalla. */
function fechaHora (v) {
  return formatBoliviaDateTime(v, '—')
}

function soloFecha (v) {
  const iso = formatBoliviaDate(v, '')
  if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '—'
  const [a, m, d] = iso.split('-')
  return `${d}/${m}/${a}`
}

function hora (v) {
  return formatBoliviaTime(v, '—')
}

// ── Número a letras (es) ────────────────────────────────────────
function enteroALetras (n) {
  if (n === 0) return 'cero'
  const u = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte']
  const v = ['', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve']
  const d = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa']
  const c = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos']
  if (n <= 20) return u[n]
  if (n < 30) return v[n - 20]
  if (n < 100) return n % 10 ? `${d[Math.floor(n / 10)]} y ${u[n % 10]}` : d[Math.floor(n / 10)]
  if (n === 100) return 'cien'
  if (n < 1000) return c[Math.floor(n / 100)] + (n % 100 ? ' ' + enteroALetras(n % 100) : '')
  if (n < 2000) return 'mil' + (n % 1000 ? ' ' + enteroALetras(n % 1000) : '')
  if (n < 1000000) return enteroALetras(Math.floor(n / 1000)) + ' mil' + (n % 1000 ? ' ' + enteroALetras(n % 1000) : '')
  if (n < 2000000) return 'un millón' + (n % 1000000 ? ' ' + enteroALetras(n % 1000000) : '')
  if (n < 1000000000) return enteroALetras(Math.floor(n / 1000000)) + ' millones' + (n % 1000000 ? ' ' + enteroALetras(n % 1000000) : '')
  return String(n)
}

function numeroALetras (monto) {
  const entero = Math.floor(Math.abs(monto))
  const centavos = Math.round((Math.abs(monto) - entero) * 100)
  return `${enteroALetras(entero).toUpperCase()} CON ${String(centavos).padStart(2, '0')}/100 Bs.`
}

/** Sobrante, faltante o caja cuadrada: el voucher lo dice con palabras. */
function leerDiferencia (valor) {
  const n = Math.round((Number(valor) || 0) * 100) / 100
  if (n > 0) return { etiqueta: 'DIFERENCIA (SOBRANTE)', monto: money(n) }
  if (n < 0) return { etiqueta: 'DIFERENCIA (FALTANTE)', monto: money(-n) }
  return { etiqueta: 'DIFERENCIA', monto: money(0) }
}

/** Un gasto de caja resta: es dinero que salió, no que entró. */
function esEgreso (venta) { return venta?.tipo_movimiento === 'EGRESO' }

function importe (venta) {
  const total = Number(venta.total || 0)
  return esEgreso(venta) ? -total : total
}

/** Totales por forma de pago de los movimientos que entraron a la caja. */
function porFormaDePago (ventas) {
  const mapa = new Map()
  for (const venta of ventas) {
    const clave = venta.tipo_pago || 'SIN DEFINIR'
    const acumulado = mapa.get(clave) || { total: 0, cantidad: 0 }
    acumulado.total += importe(venta)
    acumulado.cantidad += 1
    mapa.set(clave, acumulado)
  }
  return [...mapa.entries()]
    .map(([forma, datos]) => ({ forma, ...datos }))
    .sort((a, b) => b.total - a.total)
}

function buildHtml (cierre, ventas, completo, verMontos) {
  const dif = leerDiferencia(cierre.diferencia)
  const cantidad = cierre.cantidad_ventas ?? ventas.length
  const formas = completo ? porFormaDePago(ventas) : []

  const gastos = ventas.filter(esEgreso)

  const filasVentas = ventas.map(v => `<tr>
    <td>${esc(v.id)}</td>
    <td>${esc(hora(v.fecha_hora_cobro || v.fecha_hora))}</td>
    <td>${esc(v.paciente?.nombre_completo || v.cliente ||
      (esEgreso(v) ? 'GASTO: ' + (v.detalles?.[0]?.nombre || 'SIN DETALLE') : 'SIN CLIENTE'))}</td>
    <td class="num">${esEgreso(v) ? '-' : ''}${money(v.total)}</td>
  </tr>`).join('')

  return `<div class="voucher">
    <table class="head"><tr>
      <td style="width:30%" class="center">
        <img class="logo" src="${window.location.origin}/logo.png" onerror="this.style.display='none'">
      </td>
      <td>
        <span class="bold">${CLINICA.nombre}</span><br>
        <span class="bold">Dirección:</span> ${CLINICA.direccion}<br>
        <span class="bold">Celular:</span> ${CLINICA.celular}<br>
        <span class="bold">CIERRE N° ${String(cierre.id).padStart(6, '0')}</span>
      </td>
    </tr></table>

    <hr class="dashed">
    <span class="bold">Cajero:</span> ${esc(cierre.user?.name || '—')}<br>
    <span class="bold">Fecha de caja:</span> ${esc(soloFecha(cierre.fecha))}<br>
    <span class="bold">Cerrado el:</span> ${esc(fechaHora(cierre.fecha_hora))}
    ${cierre.modificado_en ? `<br><span class="bold">Corregido el:</span> ${esc(fechaHora(cierre.modificado_en))}` : ''}
    <br><span class="bold">Impreso el:</span> ${esc(fechaHora(new Date()))}

    <hr class="dashed">
    <div class="titulo">COMPROBANTE DE CIERRE DE CAJA</div>

    <table class="montos">
      <tr><td class="etiqueta">Ventas del día</td><td class="valor">${cantidad}</td></tr>
      ${gastos.length ? `<tr><td class="etiqueta">Gastos del día (${gastos.length}) (Bs)</td><td class="valor">-${money(gastos.reduce((suma, g) => suma + Number(g.total || 0), 0))}</td></tr>` : ''}
      ${verMontos ? `<tr><td class="etiqueta">Total según el sistema (Bs)</td><td class="valor">${money(cierre.monto_sistema)}</td></tr>
      <tr><td class="etiqueta">${dif.etiqueta} (Bs)</td><td class="valor">${dif.monto}</td></tr>` : ''}
      <tr class="fuerte"><td>EFECTIVO DECLARADO (Bs)</td><td class="right">${money(cierre.monto)}</td></tr>
    </table>
    <div class="son">SON: ${esc(numeroALetras(Number(cierre.monto || 0)))}</div>

    ${formas.length ? `<div class="sub">Por forma de pago</div>
    <table class="montos">
      ${formas.map(f => `<tr><td class="etiqueta">${esc(f.forma)} (${f.cantidad})</td><td class="valor">${money(f.total)}</td></tr>`).join('')}
    </table>` : ''}

    ${ventas.length ? `<div class="sub">Movimientos incluidos</div>
    <table class="items">
      <thead><tr><th>N°</th><th>Hora</th><th>Cliente / Paciente</th><th class="num">Total</th></tr></thead>
      <tbody>${filasVentas}</tbody>
    </table>
    ${completo ? '' : `<div class="nota">Se listan los primeros ${ventas.length} movimientos. El detalle completo está en el PDF del cierre.</div>`}`
      : '<div class="nota">Este cierre no tiene movimientos registrados.</div>'}

    ${cierre.comentario ? `<hr class="dashed"><span class="bold">Comentario:</span> ${esc(cierre.comentario)}` : ''}

    <table class="firmas"><tr>
      <td><div class="linea">Entregué conforme — ${esc(cierre.user?.name || 'Cajero')}</div></td>
      <td><div class="linea">Recibí conforme</div></td>
    </tr></table>

    <hr class="dashed" style="margin-top:14px">
    <div class="pie">${CLINICA.pie.join('<br>')}</div>
  </div>`
}

/**
 * @param {object} cierre  Cierre guardado (con su usuario cargado).
 * @param {Array}  ventas  Ventas que componen la caja; puede venir recortada.
 * @param {object} [opciones]
 * @param {number} [opciones.totalVentas]  Cuántas ventas tiene el cierre en
 *   total, para avisar en el voucher si la lista va incompleta.
 * @param {boolean} [opciones.verMontos]  Sin 'Ver Montos Caja' el voucher sale
 *   solo con lo declarado: ni el total del sistema ni la diferencia.
 */
export function imprimirCierreCaja (cierre, ventas = [], opciones = {}) {
  const { totalVentas = null, verMontos = true } = opciones
  const lista = Array.isArray(ventas) ? ventas : []
  const completo = totalVentas === null || lista.length >= totalVentas

  const element = document.createElement('div')
  element.innerHTML = buildHtml(cierre, lista, completo, verMontos)

  const printer = new Printd()
  printer.print(element, [CSS], [], ({ launchPrint }) => launchPrint())
}
