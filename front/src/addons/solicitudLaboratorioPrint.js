import { Printd } from 'printd'

export async function imprimirSolicitudLaboratorio (axios, id) {
  const { data } = await axios.get(`solicitudes-laboratorio/${id}/impresion`, { responseType: 'text' })
  const informe = new DOMParser().parseFromString(data, 'text/html')
  const estilos = Array.from(informe.querySelectorAll('style'), style => style.textContent)
  const elemento = document.createElement('div')
  elemento.innerHTML = informe.body.innerHTML

  return new Promise((resolve, reject) => {
    const printer = new Printd()
    printer.onAfterPrint(() => printer.getIFrame().remove())
    printer.print(elemento, estilos, [], ({ iframe, launchPrint }) => {
      try {
        iframe.contentWindow.focus()
        launchPrint()
        resolve()
      } catch (error) {
        iframe.remove()
        reject(error)
      }
    })
  })
}
