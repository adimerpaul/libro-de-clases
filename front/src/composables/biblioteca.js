import { mensajeError } from './useCrud'

// Tipos de recurso de la biblioteca pedagógica (back: Recurso::TIPOS) con su ícono y fondo de miniatura (ui3 .resourceThumb)
export const TIPOS_RECURSO = {
  Actividad: { icon: 'fas fa-puzzle-piece', thumb: '' },
  'Presentación': { icon: 'fas fa-chart-column', thumb: 'presentation' },
  'Guía': { icon: 'far fa-file-lines', thumb: '' },
  Video: { icon: 'fas fa-circle-play', thumb: 'video' },
  'Material didáctico': { icon: 'fas fa-shapes', thumb: 'didactic' },
  'DUA / PIE': { icon: 'fas fa-universal-access', thumb: 'didactic' },
  'Evaluación': { icon: 'fas fa-flask', thumb: 'presentation' },
}

export const tipoRecurso = tipo => TIPOS_RECURSO[tipo] || { icon: 'fas fa-toolbox', thumb: '' }

/** 1536000 → "1,5 MB" */
export function formatoTamano (bytes) {
  if (!bytes) return ''
  const kb = bytes / 1024
  return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1).replace('.', ',')} MB`
}

/** Descarga el archivo adjunto (la API exige el token, por eso no basta un enlace). */
export function descargarArchivo (proxy, recurso) {
  return proxy.$axios.get(`biblioteca/${recurso.id}/archivo`, { responseType: 'blob' })
    .then(res => {
      const url = URL.createObjectURL(res.data)
      const a = document.createElement('a')
      a.href = url
      a.download = recurso.archivo_nombre || recurso.titulo
      a.click()
      URL.revokeObjectURL(url)
    })
    .catch(err => proxy.$alert.error(err?.response?.status === 404 ? 'El archivo ya no está disponible' : mensajeError(err)))
}
