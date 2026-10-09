import { Printd } from 'printd'

const CSS = `
  @page { size: letter portrait; margin: 0; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #171717; }
  .pagina {
    width: 216mm;
    height: 279mm;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .rotulo {
    width: 118mm;
    min-height: 73mm;
    padding: 7mm 10mm 8mm;
    border: 0.65mm solid #222;
    border-radius: 9mm;
  }
  .logo { display: block; width: 70mm; height: auto; margin: 0 auto 1mm; }
  .titulo {
    margin: 0 0 3mm;
    color: #2d82b7;
    font-size: 13pt;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
  }
  table { width: 100%; border-collapse: collapse; font-size: 10.5pt; line-height: 1.35; }
  th { width: 39mm; padding: 0.45mm 2mm 0.45mm 0; color: #2d82b7; text-align: right; vertical-align: top; }
  td { padding: 0.45mm 0; font-weight: 600; text-transform: uppercase; }
`

function esc (value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function fechaTranscripcion () {
  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'America/La_Paz',
  }).format(new Date())
}

export function imprimirRotuloSolicitudLaboratorio (solicitud, imgBase = '') {
  const logoUrl = `${String(imgBase).replace(/\/$/, '')}/images/logo-laboratorio-urme.jpg`
  const html = `
    <main class="pagina">
      <section class="rotulo">
        <img class="logo" src="${esc(logoUrl)}" alt="Laboratorio de Diagnóstico Clínico URME">
        <div class="titulo">Laboratorio de Diagnóstico Clínico URME</div>
        <table>
          <tr><th>Nombre:</th><td>${esc(solicitud.paciente?.nombre_completo || 'NO ASIGNADO')}</td></tr>
          <tr><th>Médico:</th><td>${esc(solicitud.doctor?.nombre || 'NO ASIGNADO')}</td></tr>
          <tr><th>Institución:</th><td>${esc(solicitud.paciente?.seguro?.nombre || 'PARTICULAR')}</td></tr>
          <tr><th>Fecha de Transcripción:</th><td>${esc(fechaTranscripcion())}</td></tr>
        </table>
      </section>
    </main>`

  const element = document.createElement('div')
  element.innerHTML = html
  new Printd().print(element, [CSS], [], ({ launchPrint }) => launchPrint())
}
