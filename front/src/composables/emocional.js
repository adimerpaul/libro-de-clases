import { ref, getCurrentInstance } from 'vue'

// Catálogos de Educación emocional (App\Support\Socioemocional en la API) y utilidades de presentación.
const catalogos = ref(null)
let pedido = null

export function useCatalogosEmocional () {
  const { proxy } = getCurrentInstance()
  if (!pedido) {
    pedido = proxy.$axios.get('emocional/catalogos')
      .then(res => { catalogos.value = res.data })
      .catch(() => { pedido = null })
  }
  return catalogos
}

/** Etiqueta de un valor en una lista [{ value, label }] del catálogo. */
export function etiqueta (lista, valor) {
  return (lista || []).find(x => x.value === valor)?.label || valor || '—'
}

export function colorCompetencia (cat, clave) {
  return (cat?.competencias || []).find(c => c.value === clave)?.color || '#7c3aed'
}

export const CLASE_ESTADO_ALERTA = {
  activa: 'red', acompanamiento: 'blue', seguimiento: 'violeta', derivacion: 'warn', estable: 'ok', cerrada: '',
}

export const CLASE_TIPO_ALERTA = { desregulacion: 'warn', bienestar: 'violeta', riesgo: 'red' }

export const CLASE_ESTADO_SESION = { planificada: '', en_curso: 'warn', realizada: 'ok', permanente: 'blue' }

/** Color de un logro 0–100 (verde ≥ 80, ámbar 65–79, rojo < 65). */
export function claseLogro (v) {
  if (v == null) return ''
  return v >= 80 ? 'ok' : v >= 65 ? 'warn' : 'red'
}

export const ICONO_TENDENCIA = { sube: 'fas fa-arrow-trend-up', estable: 'fas fa-arrow-right', baja: 'fas fa-arrow-trend-down' }
