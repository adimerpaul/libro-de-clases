import { Printd } from 'printd'

const CSS = `
  @page { size: letter landscape; margin: 10mm; }
  body { margin: 0; font-family: Arial, sans-serif; color: #172033; font-size: 9px; }
  h1 { margin: 0; color: #0d47a1; font-size: 18px; } h2 { margin: 14px 0 4px; font-size: 12px; color: #0d47a1; }
  .head { border-bottom: 3px solid #0d47a1; padding-bottom: 7px; } .muted { color: #64748b; }
  .summary { display: table; width: 100%; margin-top: 8px; border-spacing: 4px; }
  .summary div { display: table-cell; border: 1px solid #90caf9; padding: 5px; text-align: center; }
  .summary b { display: block; color: #0d47a1; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #e3f2fd; color: #0d47a1; text-align: left; }
  th, td { border: 1px solid #b9c9dc; padding: 3px 4px; }
  .right { text-align: right; } .center { text-align: center; }
  .pend th { background: #ffe9d1; color: #a04a00; }
  .done th { background: #d9f2e3; color: #1b5e20; }
`

function esc (value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}
function money (value) { return Number(value || 0).toFixed(2) }
function fecha (value) { return value ? String(value).slice(0, 10) : '' }
function cargos (internacion) {
  return (internacion.items || []).reduce((sum, item) => sum + Number(item.total || 0), 0)
}

// Planilla del seguro: las mismas columnas que la hoja que lleva la clínica.
function planilla (titulo, filas, clase) {
  return `<h2>${esc(titulo)} (${filas.length})</h2>
    <table class="${clase}"><thead><tr>
      <th class="center">Nº</th><th>Paciente</th>
      <th class="center">Entrega de informe</th><th class="center">Respuesta de auditoría</th>
      <th class="center">Fecha de facturación</th><th class="right">Monto facturado</th>
      <th class="center">Fecha de cancelación</th><th>Tipo de pago</th><th class="right">Cargos Bs</th>
    </tr></thead><tbody>
    ${filas.map((i, idx) => `<tr>
      <td class="center">${idx + 1}</td>
      <td>${esc(i.paciente?.nombre_completo || '—')}</td>
      <td class="center">${esc(fecha(i.entrega_informe))}</td>
      <td class="center">${esc(fecha(i.respuesta_auditoria))}</td>
      <td class="center">${esc(fecha(i.fecha_facturacion))}</td>
      <td class="right">${i.monto_facturado != null ? money(i.monto_facturado) : ''}</td>
      <td class="center">${esc(fecha(i.fecha_cancelacion))}</td>
      <td>${esc(i.tipo_pago || '')}</td>
      <td class="right">${money(cargos(i))}</td>
    </tr>`).join('') || '<tr><td colspan="9" class="center">Sin internaciones</td></tr>'}
    </tbody></table>`
}

export function imprimirSeguro (detalle) {
  const pacientes = detalle.pacientes || []
  const internaciones = detalle.internaciones || []
  const resumen = detalle.resumen || {}
  const pendientes = internaciones.filter(i => i.seguimiento_estado !== 'COMPLETADO')
  const completados = internaciones.filter(i => i.seguimiento_estado === 'COMPLETADO')

  const html = `<div class="head"><h1>Planilla de seguro</h1>
    <div><b>${esc(detalle.seguro?.nombre)}</b> · NIT: ${esc(detalle.seguro?.nit || '—')}
    ${detalle.mes ? ' · Mes: ' + esc(detalle.mes) : ''}</div>
    <div class="muted">Generado: ${esc(new Date().toLocaleString('es-BO'))}</div></div>
    <div class="summary">
      <div><b>${resumen.cantidad_pacientes || 0}</b>Pacientes afiliados</div>
      <div><b>${resumen.cantidad_internaciones || 0}</b>Internaciones</div>
      <div><b>${resumen.pendientes || 0}</b>Pendientes</div>
      <div><b>${resumen.completados || 0}</b>Completados</div>
      <div><b>Bs ${money(resumen.total)}</b>Total cargos</div>
      <div><b>Bs ${money(resumen.total_facturado)}</b>Total facturado</div>
    </div>
    ${planilla('Pendientes', pendientes, 'pend')}
    ${planilla('Completados', completados, 'done')}
    <h2>Pacientes afiliados (${pacientes.length})</h2>
    <table><thead><tr><th>Paciente</th><th>CI</th><th>Teléfono</th></tr></thead><tbody>
    ${pacientes.map(p => `<tr><td>${esc(p.nombre_completo)}</td><td>${esc(p.ci || '—')}</td><td>${esc(p.telefono || '—')}</td></tr>`).join('') || '<tr><td colspan="3">Sin pacientes afiliados</td></tr>'}
    </tbody></table>`

  const element = document.createElement('div')
  element.innerHTML = html
  new Printd().print(element, [CSS], [], ({ launchPrint }) => launchPrint())
}
