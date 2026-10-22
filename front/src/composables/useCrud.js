import { ref, getCurrentInstance } from 'vue'

/** Mensaje legible de un error de la API (validación 422, 403, 409…). */
export function mensajeError (err) {
  const data = err?.response?.data
  const primero = data?.errors && Object.values(data.errors)[0]?.[0]
  return primero || data?.message || 'No se pudo conectar con el servidor'
}

/**
 * Listado + formulario en diálogo para un recurso REST de la API.
 * @param {string} url       p. ej. 'admin/cursos'
 * @param {Function} vacio   devuelve el formulario en blanco
 * @param {Object} opciones  params(): filtros del GET · aForm(row): fila → formulario · nombre: para los avisos
 */
export function useCrud (url, vacio, { params = () => ({}), aForm = row => ({ ...row }), nombre = 'Registro' } = {}) {
  const { proxy } = getCurrentInstance()

  const rows = ref([])
  const loading = ref(false)
  const dialog = ref(false)
  const saving = ref(false)
  const form = ref(vacio())

  function cargar () {
    loading.value = true
    return proxy.$axios.get(url, { params: params() })
      .then(res => { rows.value = res.data })
      .catch(err => proxy.$alert.error(mensajeError(err)))
      .finally(() => { loading.value = false })
  }

  function nuevo (extra = {}) {
    form.value = { ...vacio(), ...extra }
    dialog.value = true
  }

  function editar (row) {
    form.value = aForm(row)
    dialog.value = true
  }

  function guardar (payload = form.value) {
    saving.value = true
    const req = form.value.id
      ? proxy.$axios.put(`${url}/${form.value.id}`, payload)
      : proxy.$axios.post(url, payload)

    return req
      .then(() => {
        proxy.$alert.success(form.value.id ? `${nombre} actualizado` : `${nombre} creado`)
        dialog.value = false
        return cargar()
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
      .finally(() => { saving.value = false })
  }

  function eliminar (row, pregunta) {
    proxy.$alert.confirm(pregunta).onOk(() => {
      proxy.$axios.delete(`${url}/${row.id}`)
        .then(res => {
          proxy.$alert.success(res.data.message || `${nombre} eliminado`)
          cargar()
        })
        .catch(err => proxy.$alert.error(mensajeError(err)))
    })
  }

  return { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar }
}

/** Catálogos fijos de administración (niveles, permisos agrupados, año actual). */
const catalogos = ref({ niveles: [], permisos: [], anio: new Date().getFullYear() })
let pedidos = false

export function useCatalogos () {
  const { proxy } = getCurrentInstance()
  if (!pedidos) {
    pedidos = true
    proxy.$axios.get('admin/catalogos')
      .then(res => { catalogos.value = res.data })
      .catch(() => { pedidos = false })
  }
  return catalogos
}
