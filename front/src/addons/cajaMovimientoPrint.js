import { Printd } from 'printd'

const CSS = `
  @page { size: 5.5in 8.5in; margin: 10mm; }
  body { margin: 0; }
  .recibo { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; line-height: 1.35; position: relative; }
  table { width: 100%; border-collapse: collapse; } td { vertical-align: top; }
  .head { margin-top: 8px; } .logo { width: 90px; } .bold { font-weight: bold; }
  .center { text-align: center; } .right { text-align: right; }
  .dashed { border: 0; border-top: 1.5px dashed #000; margin: 6px 0; }
  .titulo { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13px; margin: 10px 0 7px; }
  .items th { text-align: left; border-bottom: 1.5px solid #000; padding: 3px 4px; }
  .items td { padding: 4px; border-bottom: 1.5px solid #000; }
  .items .importe { border-left: 1.5px solid #000; text-align: right; }
  .total { font-weight: bold; font-size: 12px; text-align: right; margin-top: 5px; }
  .son { text-align: center; font-weight: bold; margin-top: 8px; }
  .datos { margin-top: 9px; } .pie { text-align: center; font-size: 10px; margin-top: 16px; }
  .anulado { position: absolute; top: 42%; left: 50%; transform: translate(-50%, -50%) rotate(-25deg);
    font-size: 48px; font-weight: bold; color: rgba(200, 0, 0, .35); letter-spacing: 5px; }
`

function esc (value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}
function money (value) { return Number(value || 0).toFixed(2).replace('.', ',') }

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

function buildHtml (movimiento, cajaLabel) {
  const ingreso = movimiento.tipo === 'INGRESO'
  const titulo = ingreso ? 'RECIBO DE INGRESO DE DINERO CAJA' : 'RECIBO DE SALIDA DE DINERO CAJA'
  const persona = ingreso ? 'Recibido de / origen' : 'Pagado a / beneficiario'
  const fecha = String(movimiento.fecha_hora || '').replace('T', ' ').slice(0, 19) || '—'
  return `<div class="recibo">
    ${movimiento.estado === 'ANULADO' ? '<div class="anulado">ANULADO</div>' : ''}
    <table class="head"><tr><td style="width:30%" class="center"><img class="logo" src="${window.location.origin}/logo.png" onerror="this.style.display='none'"></td>
    <td><span class="bold">CLÍNICA URME</span><br><span class="bold">Dirección:</span> Calle Cochabamba entre Soria Galvarro y 6 de Octubre<br><span class="bold">Celular:</span> 70431083<br><span class="bold">RECIBO N° ${String(movimiento.id).padStart(6, '0')}</span></td></tr></table>
    <hr class="dashed"><span class="bold">Cajero:</span> ${esc(movimiento.user?.name || '—')}<br><span class="bold">Fecha:</span> ${esc(fecha)}<br><span class="bold">Caja:</span> ${esc(cajaLabel)}
    <hr class="dashed"><div class="titulo">${titulo}</div>
    <table class="items"><thead><tr><th>Descripción</th><th class="importe">Importe</th></tr></thead><tbody><tr><td><span class="bold">${esc(movimiento.concepto)}</span><br>${esc(movimiento.descripcion || '')}</td><td class="importe">${money(movimiento.importe)}</td></tr></tbody></table>
    <div class="total">TOTAL Bs. : ${money(movimiento.importe)}</div><div class="son">SON: ${esc(numeroALetras(Number(movimiento.importe || 0)))}</div>
    <div class="datos"><span class="bold">${persona}:</span> ${esc(movimiento.beneficiario || '—')}<br><span class="bold">Documento:</span> ${esc(movimiento.documento || '—')}<br><span class="bold">Categoría:</span> ${esc(movimiento.categoria || '—')}</div>
    ${movimiento.estado === 'ANULADO' ? `<div class="datos"><span class="bold">Motivo de anulación:</span> ${esc(movimiento.motivo_anulacion || '—')}<br><span class="bold">Anulado por:</span> ${esc(movimiento.anulado_por?.name || '—')}</div>` : ''}
    <hr class="dashed" style="margin-top:16px"><div class="pie">"CLÍNICA URME" Al servicio de Oruro, Atención de Emergencias<br>las 24 horas los 365 días del año.<br>Atención en todas las Especialidades</div>
  </div>`
}

export function imprimirCajaMovimiento (movimiento, cajaLabel) {
  const element = document.createElement('div')
  element.innerHTML = buildHtml(movimiento, cajaLabel)
  const printer = new Printd()
  printer.print(element, [CSS], [], ({ launchPrint }) => launchPrint())
}
