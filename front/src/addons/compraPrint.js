import { Printd } from 'printd'

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
  .proforma { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; line-height: 1.35; }
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
`

function esc (v) {
  return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}

function money (v) {
  return Number(v || 0).toFixed(2).replace('.', ',')
}

function cantidad (v) {
  const n = Number(v || 0)
  return Number.isInteger(n) ? String(n) : n.toFixed(2).replace('.', ',')
}

// ── Número a letras (es) ────────────────────────────────────────
function numeroALetras (monto, moneda = 'BOLIVIANOS') {
  const entero = Math.floor(Math.abs(monto))
  const decimales = Math.round((Math.abs(monto) - entero) * 100)
  const texto = enteroALetras(entero).toUpperCase()
  if (decimales > 0) return `${texto} ${moneda} CON ${String(decimales).padStart(2, '0')}/100`
  return `${texto} ${moneda} EXACTOS`
}

function enteroALetras (n) {
  if (n === 0) return 'cero'
  const unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
    'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte']
  const veintes = ['', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco',
    'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve']
  const decenas = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa']
  const centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
    'seiscientos', 'setecientos', 'ochocientos', 'novecientos']

  if (n <= 20) return unidades[n]
  if (n < 30) return veintes[n - 20]
  if (n < 100) {
    const d = Math.floor(n / 10)
    const u = n % 10
    return u === 0 ? decenas[d] : `${decenas[d]} y ${unidades[u]}`
  }
  if (n === 100) return 'cien'
  if (n < 1000) {
    const c = Math.floor(n / 100)
    const resto = n % 100
    return centenas[c] + (resto > 0 ? ' ' + enteroALetras(resto) : '')
  }
  if (n < 2000) {
    const resto = n % 1000
    return 'mil' + (resto > 0 ? ' ' + enteroALetras(resto) : '')
  }
  if (n < 1000000) {
    const miles = Math.floor(n / 1000)
    const resto = n % 1000
    return enteroALetras(miles) + ' mil' + (resto > 0 ? ' ' + enteroALetras(resto) : '')
  }
  if (n < 2000000) {
    const resto = n % 1000000
    return 'un millón' + (resto > 0 ? ' ' + enteroALetras(resto) : '')
  }
  if (n < 1000000000) {
    const millones = Math.floor(n / 1000000)
    const resto = n % 1000000
    return enteroALetras(millones) + ' millones' + (resto > 0 ? ' ' + enteroALetras(resto) : '')
  }
  return String(n)
}

// ── HTML de la proforma ─────────────────────────────────────────
function buildHtml (compra) {
  const logoUrl = window.location.origin + '/logo.png'
  const fecha = (compra.fecha_hora || '').replace('T', ' ').slice(0, 19) || '—'
  const detalles = compra.detalles || []

  const filas = detalles.length
    ? detalles.map(det => `
        <tr>
          <td>${esc(det.nombre || det.producto?.nombre || '—')}</td>
          <td class="right">${cantidad(det.cantidad)}</td>
          <td class="right precio">${money(det.precio)}</td>
          <td class="right">${money(det.total)}</td>
        </tr>`).join('')
    : '<tr><td colspan="4" class="center">Sin detalles</td></tr>'

  return `
    <div class="proforma">
      <div class="titulo-doc">COMPRA DE FARMACIA</div>

      <table class="head">
        <tr>
          <td style="width:30%" class="center">
            <img class="logo" src="${logoUrl}" alt="Logo" onerror="this.style.display='none'">
          </td>
          <td style="width:70%">
            <span class="bold">${esc(CLINICA.nombre)}</span><br>
            <span class="bold">Dirección:</span> ${esc(CLINICA.direccion)}<br>
            <span class="bold">Celular:</span> ${esc(CLINICA.celular)}<br>
            <span class="bold">COMPRA N° ${String(compra.id).padStart(6, '0')}</span>
          </td>
        </tr>
      </table>

      <hr class="dashed">

      <table>
        <tr>
          <td style="width:65%">
            <span class="bold">Usuario:</span> ${esc(compra.user?.name || '—')}<br>
            <span class="bold">Fecha:</span> ${esc(fecha)}<br>
            <span class="bold">Proveedor:</span> ${esc(compra.proveedor?.nombre || 'SIN PROVEEDOR')}<br>
            <span class="bold">Pago:</span> ${esc(compra.tipo_pago || '—')}
          </td>
          <td style="width:35%">
            <span class="bold">Factura N°:</span> ${esc(compra.nro_factura || '—')}<br>
            <span class="bold">Estado:</span> ${esc(compra.estado || '—')}
          </td>
        </tr>
      </table>

      <div class="detalle-title">DETALLE DE COMPRA</div>

      <table class="items">
        <thead>
          <tr>
            <th style="width:56%">Descripción</th>
            <th style="width:12%" class="right">Cant.</th>
            <th style="width:16%" class="right precio">Precio</th>
            <th style="width:16%" class="right">Total</th>
          </tr>
        </thead>
        <tbody>${filas}</tbody>
      </table>

      <table class="totales">
        <tr>
          <td style="width:68%" class="right">TOTAL Bs. :</td>
          <td style="width:32%" class="right monto">${money(compra.total)}</td>
        </tr>
      </table>

      <div class="son">SON: ${esc(numeroALetras(Number(compra.total || 0)))}</div>

      ${compra.comentario ? `<div class="obs"><span class="bold">Obs.:</span> ${esc(compra.comentario)}</div>` : ''}

      <hr class="dashed" style="margin-top:16px">
      <div class="pie">${CLINICA.pie.map(esc).join('<br>')}</div>
    </div>
  `
}

// ── Imprimir directamente con Printd ────────────────────────────
export function imprimirCompra (compra) {
  const el = document.createElement('div')
  el.innerHTML = buildHtml(compra)

  const d = new Printd()
  d.print(el, [CSS], [], ({ launchPrint }) => launchPrint())
}
