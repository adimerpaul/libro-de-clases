import { QSelect } from 'quasar'
import { ref, computed } from 'vue'

/**
 * Todos los q-select con opciones locales se pueden escribir para buscar (sin tocar cada página).
 * Se activa solo si el select no tiene ya su propio filtro (`use-input` / `@filter`, p. ej.
 * búsquedas al servidor) y tiene al menos MINIMO opciones. Para desactivarlo en uno: `:buscar="false"`.
 * La búsqueda ignora mayúsculas y tildes ("matematica" encuentra "Matemática").
 */
const MINIMO = 6

const normalizar = s => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

function etiqueta (props, opt) {
  if (opt === null || typeof opt !== 'object') return opt
  const ol = props.optionLabel
  if (typeof ol === 'function') return ol(opt)
  return opt[ol || 'label']
}

export default () => {
  if (QSelect.__buscable) return
  QSelect.__buscable = true

  QSelect.props = { ...QSelect.props, buscar: { type: Boolean, default: true } }
  const setupOriginal = QSelect.setup

  QSelect.setup = (props, ctx) => {
    const texto = ref('')

    const activo = computed(() =>
      props.buscar !== false &&
      props.useInput !== true &&
      props.onFilter === undefined &&
      Array.isArray(props.options) &&
      props.options.length >= MINIMO,
    )

    const opciones = computed(() => {
      const q = normalizar(texto.value).trim()
      if (!activo.value || !q) return props.options
      return props.options.filter(o => normalizar(etiqueta(props, o)).includes(q))
    })

    const filtrar = (val, update) => update(() => { texto.value = val })

    const propsBuscables = new Proxy(props, {
      get (t, k, r) {
        if (k === 'options') return opciones.value
        if (k === 'useInput') return activo.value || t.useInput
        if (k === 'onFilter') return activo.value ? filtrar : t.onFilter
        if (k === 'inputDebounce') return activo.value ? 0 : t.inputDebounce
        return Reflect.get(t, k, r)
      },
    })

    const emit = (evento, ...args) => {
      if (evento === 'filter' && activo.value) return filtrar(...args)
      return ctx.emit(evento, ...args)
    }

    return setupOriginal(propsBuscables, { attrs: ctx.attrs, slots: ctx.slots, expose: ctx.expose, emit })
  }
}
