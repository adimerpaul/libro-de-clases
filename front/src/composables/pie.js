import { ref } from 'vue'
import { mensajeError } from './useCrud'

// Estado del caso PIE (back: App\Support\Pie::ESTADOS) → etiqueta y color de .pill
export const ESTADOS_PIE = {
  en_evaluacion: { label: 'En evaluación', pill: 'amber' },
  activo: { label: 'Activo', pill: 'green' },
  egresado: { label: 'Egresado / alta', pill: '' },
}

export const TIPOS_NEE = {
  transitoria: { label: 'NEE transitoria', badge: 'blue' },
  permanente: { label: 'NEE permanente', badge: 'violeta' },
}

/** "Silva Rojas, Catalina" → "CS" */
export function iniciales (estudiante) {
  if (!estudiante) return '?'
  return `${(estudiante.nombres || '')[0] || ''}${(estudiante.apellido_paterno || '')[0] || ''}`.toUpperCase()
}

/** "2026-03-10" → "10-03-2026" */
export const fecha = v => (v ? v.split('-').reverse().join('-') : '—')

/** Días que faltan para una fecha (negativo si ya pasó). */
export function diasHasta (v) {
  if (!v) return null
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  return Math.round((new Date(`${v}T00:00:00`) - hoy) / 86400000)
}

/** Catálogos del módulo (diagnósticos, apoyos, especialidades, responsables…), pedidos una vez. */
const catalogos = ref({
  diagnosticos: [], estados: [], apoyos: [], especialidades: [], tipos_documento: [],
  tipos_seguimiento: [], areas: [], responsables: [], anio: new Date().getFullYear(),
})
let pedidos = false

export function useCatalogosPie (proxy) {
  if (!pedidos) {
    pedidos = true
    proxy.$axios.get('pie/catalogos')
      .then(res => { catalogos.value = res.data })
      .catch(err => { pedidos = false; proxy.$alert.error(mensajeError(err)) })
  }
  return catalogos
}

/** El archivo del expediente exige el token: se pide como blob y se arma una URL local. */
export function urlArchivo (proxy, documento) {
  return proxy.$axios.get(`pie/documentos/${documento.id}/archivo`, { responseType: 'blob' })
    .then(res => URL.createObjectURL(res.data))
}

export function descargarDocumento (proxy, documento) {
  return urlArchivo(proxy, documento)
    .then(url => {
      const a = document.createElement('a')
      a.href = url
      a.download = documento.archivo_nombre
      a.click()
      setTimeout(() => URL.revokeObjectURL(url), 1000)
    })
    .catch(err => proxy.$alert.error(err?.response?.status === 404 ? 'El archivo ya no está disponible' : mensajeError(err)))
}

/** Abre PDF o imagen en una pestaña nueva (el navegador lo muestra). */
export function abrirDocumento (proxy, documento) {
  const ventana = window.open('', '_blank')
  return urlArchivo(proxy, documento)
    .then(url => { if (ventana) ventana.location.href = url; else window.location.href = url })
    .catch(err => {
      ventana?.close()
      proxy.$alert.error(err?.response?.status === 404 ? 'El archivo ya no está disponible' : mensajeError(err))
    })
}
