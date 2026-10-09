<template>
  <q-page class="q-pa-md derivaciones-page">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Derivaciones de laboratorio</div>
        <div class="text-caption text-grey-6">Registro, recorte e impresión de resultados externos</div>
      </div>
      <q-space />
      <q-badge color="primary" class="q-pa-sm">{{ pagination.rowsNumber }} registros</q-badge>
    </div>

    <q-tabs v-model="tab" dense align="left" active-color="primary" indicator-color="primary" class="text-grey-7">
      <q-tab name="formulario" icon="add_photo_alternate" :label="form.id ? 'Editar derivación' : 'Crear derivación'" />
      <q-tab name="registros" icon="view_list" label="Derivaciones creadas" />
    </q-tabs>
    <q-separator />

    <q-tab-panels v-model="tab" animated>
      <q-tab-panel name="formulario" class="q-pa-md">
        <q-form @submit.prevent="guardar">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-5">
              <q-card flat bordered>
                <q-card-section class="q-pb-sm">
                  <div class="text-subtitle2 text-weight-bold">Datos de la derivación</div>
                </q-card-section>
                <q-card-section class="q-pt-none q-gutter-sm">
                  <q-input v-model="form.fecha" outlined dense type="date" label="Fecha *"
                           :rules="[v => !!v || 'Requerido']" />
                  <q-input v-model="form.paciente" outlined dense label="Paciente *" v-uppercase
                           :rules="[v => !!v || 'Requerido']" />
                  <q-input v-model="form.servicio" outlined dense label="Servicio / examen" v-uppercase />
                  <q-input v-model="form.laboratorio_destino" outlined dense label="Laboratorio de destino" v-uppercase />
                  <q-input v-model="form.observaciones" outlined dense type="textarea" rows="3"
                           label="Observaciones" v-uppercase />
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-7">
              <q-card flat bordered class="full-height">
                <q-card-section class="row items-center q-pb-sm">
                  <div>
                    <div class="text-subtitle2 text-weight-bold">Imagen del resultado {{ form.id ? '' : '*' }}</div>
                    <div class="text-caption text-grey-6">JPG, PNG o WEBP, máximo 10 MB</div>
                  </div>
                  <q-space />
                  <q-file v-model="archivoOrigen" accept="image/jpeg,image/png,image/webp" dense outlined
                          label="Seleccionar imagen" style="width:230px" @update:model-value="seleccionarImagen">
                    <template #prepend><q-icon name="upload" /></template>
                  </q-file>
                </q-card-section>
                <q-card-section class="q-pt-none">
                  <div v-if="imagenPreview" class="preview-wrap">
                    <img :src="imagenPreview" alt="Vista previa de derivación">
                    <div class="row justify-center q-gutter-sm q-mt-sm">
                      <q-btn outline dense color="primary" icon="crop" label="Recortar" no-caps @click="abrirRecorte" />
                      <q-btn v-if="imagenNueva" flat dense color="negative" icon="delete" label="Quitar" no-caps @click="quitarImagen" />
                    </div>
                  </div>
                  <div v-else class="drop-empty column items-center justify-center text-grey-5">
                    <q-icon name="image" size="64px" />
                    <div>Seleccione la fotografía o escaneo del resultado</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <div class="row justify-end q-gutter-sm q-mt-md">
            <q-btn v-if="form.id" flat color="grey-7" label="Cancelar edición" no-caps @click="limpiarFormulario" />
            <q-btn rounded unelevated color="primary" icon="save" :label="form.id ? 'Guardar cambios' : 'Crear derivación'"
                   no-caps type="submit" :loading="guardando" :disable="!canGuardar" />
          </div>
        </q-form>
      </q-tab-panel>

      <q-tab-panel name="registros" class="q-pa-sm">
        <div class="row q-col-gutter-sm items-center q-mb-sm">
          <div class="col-12 col-sm-3">
            <q-input v-model="filters.q" dense outlined clearable debounce="400" label="Paciente, servicio o laboratorio"
                     @update:model-value="recargar"><template #prepend><q-icon name="search" /></template></q-input>
          </div>
          <div class="col-6 col-sm-2">
            <q-input v-model="filters.desde" dense outlined type="date" label="Desde" @update:model-value="recargar" />
          </div>
          <div class="col-6 col-sm-2">
            <q-input v-model="filters.hasta" dense outlined type="date" label="Hasta" @update:model-value="recargar" />
          </div>
          <div class="col-10 col-sm-3">
            <q-select v-model="filters.user_id" dense outlined clearable emit-value map-options
                      :options="usuarios" option-value="id" option-label="name" label="Usuario"
                      @update:model-value="recargar" />
          </div>
          <div class="col-2 col-sm-2 row justify-end">
            <q-btn flat round color="primary" icon="refresh" :loading="loading" @click="recargar" />
          </div>
        </div>

        <q-table ref="tableRef" flat bordered dense row-key="id" :rows="rows" :columns="columns"
                 :loading="loading" v-model:pagination="pagination" :rows-per-page-options="[15, 25, 50, 100]"
                 @request="onRequest" no-data-label="No existen derivaciones para el filtro seleccionado">
          <template #body-cell-imagen="props">
            <q-td :props="props">
              <q-img :src="imagenUrl(props.row)" width="54px" height="54px" fit="cover" class="rounded-borders cursor-pointer"
                     @click="verImagen(props.row)" />
            </q-td>
          </template>
          <template #body-cell-opciones="props">
            <q-td :props="props">
              <q-btn-dropdown dense unelevated rounded color="primary" label="Opciones" no-caps size="10px">
                <q-list dense style="min-width:180px">
                  <q-item clickable v-close-popup @click="imprimir(props.row)">
                    <q-item-section avatar><q-icon name="print" color="indigo" /></q-item-section>
                    <q-item-section>Imprimir</q-item-section>
                  </q-item>
                  <q-item v-if="canEditar" clickable v-close-popup @click="editar(props.row)">
                    <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                    <q-item-section>Modificar</q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="eliminar(props.row)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section class="text-negative">Eliminar</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
      </q-tab-panel>
    </q-tab-panels>

    <q-dialog v-model="cropDialog" persistent>
      <q-card style="width:min(96vw,820px);max-width:820px">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="crop" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">Recortar imagen</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <div class="crop-stage" :class="{ 'crop-stage--dragging': draggingCrop }">
            <div class="crop-canvas"
                 @pointerdown="iniciarCreacionRecorte" @pointermove="moverRecorte"
                 @pointerup="finalizarArrastreRecorte" @pointercancel="finalizarArrastreRecorte">
              <img :src="imagenOrigen" alt="Imagen para recortar" draggable="false">
              <template v-if="cropSeleccionado">
                <div class="crop-mask crop-mask--top" :style="maskTopStyle" />
                <div class="crop-mask crop-mask--bottom" :style="maskBottomStyle" />
                <div class="crop-mask crop-mask--left" :style="maskLeftStyle" />
                <div class="crop-mask crop-mask--right" :style="maskRightStyle" />
                <div class="crop-frame" :style="cropFrameStyle" @pointerdown.stop="iniciarMovimientoRecorte">
                  <span class="crop-handle crop-handle--nw" @pointerdown.stop="iniciarRedimensionRecorte($event, 'nw')" />
                  <span class="crop-handle crop-handle--ne" @pointerdown.stop="iniciarRedimensionRecorte($event, 'ne')" />
                  <span class="crop-handle crop-handle--sw" @pointerdown.stop="iniciarRedimensionRecorte($event, 'sw')" />
                  <span class="crop-handle crop-handle--se" @pointerdown.stop="iniciarRedimensionRecorte($event, 'se')" />
                </div>
              </template>
            </div>
          </div>
          <div class="text-caption text-grey-7 text-center q-mt-xs">
            Arrastre sobre la imagen para crear el recorte. Luego arrastre el recuadro para moverlo o use las esquinas para cambiar su tamaño.
          </div>
          <div class="row q-col-gutter-lg q-mt-sm">
            <div class="col-12 col-sm-6">
              <div class="text-caption">Recorte horizontal</div>
              <q-range v-model="cropX" :min="0" :max="100" :step="1" label color="primary"
                       @update:model-value="cropSeleccionado = true" />
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption">Recorte vertical</div>
              <q-range v-model="cropY" :min="0" :max="100" :step="1" label color="primary"
                       @update:model-value="cropSeleccionado = true" />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat color="grey-7" label="Cancelar" no-caps v-close-popup />
          <q-btn unelevated color="primary" icon="crop" label="Aplicar recorte" no-caps @click="aplicarRecorte" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="imagenDialog">
      <q-card style="width:min(96vw,900px);max-width:900px">
        <q-card-section class="row items-center q-py-sm">
          <span class="text-subtitle2 text-weight-bold">{{ imagenSeleccionada?.paciente }}</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-none text-center">
          <img v-if="imagenSeleccionada" :src="imagenUrl(imagenSeleccionada)" class="imagen-completa" alt="Derivación">
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
import { nowBoliviaDateTimeInput } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()
const tab = ref('formulario')
const tableRef = ref(null)
const rows = ref([])
const usuarios = ref([])
const loading = ref(false)
const guardando = ref(false)
const archivoOrigen = ref(null)
const imagenOrigen = ref('')
const imagenPreview = ref('')
const imagenNueva = ref(null)
const cropDialog = ref(false)
const cropX = ref({ min: 0, max: 100 })
const cropY = ref({ min: 0, max: 100 })
const draggingCrop = ref(false)
const dragStart = ref(null)
const resizeHandle = ref(null)
const cropDragMode = ref(null)
const cropSeleccionado = ref(false)
const imagenDialog = ref(false)
const imagenSeleccionada = ref(null)

const hoy = nowBoliviaDateTimeInput().slice(0, 10)

function semanaActual () {
  const fecha = new Date(`${hoy}T12:00:00`)
  const dia = fecha.getDay() || 7
  const lunes = new Date(fecha)
  lunes.setDate(fecha.getDate() - dia + 1)
  const domingo = new Date(lunes)
  domingo.setDate(lunes.getDate() + 6)
  const ymd = value => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  return { desde: ymd(lunes), hasta: ymd(domingo) }
}

const semana = semanaActual()
const filters = ref({ q: '', desde: semana.desde, hasta: semana.hasta, user_id: null })
const pagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })
const form = ref(formVacio())

const canCrear = computed(() => proxy.$store.hasPermission('Crear Derivaciones'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Derivaciones'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Derivaciones'))
const canGuardar = computed(() => form.value.id ? canEditar.value : canCrear.value)

const columns = [
  { name: 'opciones', label: 'Opciones', field: 'id', align: 'left' },
  { name: 'imagen', label: 'Imagen', field: 'imagen', align: 'center' },
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left' },
  { name: 'paciente', label: 'Paciente', field: 'paciente', align: 'left' },
  { name: 'servicio', label: 'Servicio', field: 'servicio', align: 'left' },
  { name: 'destino', label: 'Laboratorio destino', field: 'laboratorio_destino', align: 'left' },
  { name: 'usuario', label: 'Registrado por', field: row => row.user?.name, align: 'left' },
]

const cropFrameStyle = computed(() => ({
  left: `${cropX.value.min}%`, right: `${100 - cropX.value.max}%`,
  top: `${cropY.value.min}%`, bottom: `${100 - cropY.value.max}%`,
}))
const maskTopStyle = computed(() => ({ height: `${cropY.value.min}%` }))
const maskBottomStyle = computed(() => ({ height: `${100 - cropY.value.max}%` }))
const maskLeftStyle = computed(() => ({ top: `${cropY.value.min}%`, bottom: `${100 - cropY.value.max}%`, width: `${cropX.value.min}%` }))
const maskRightStyle = computed(() => ({ top: `${cropY.value.min}%`, bottom: `${100 - cropY.value.max}%`, width: `${100 - cropX.value.max}%` }))

function formVacio () {
  return { id: null, fecha: hoy, paciente: '', laboratorio_destino: '', servicio: '', observaciones: '' }
}

function imagenUrl (row) {
  return `${proxy.$imgBase}${row.imagen_url}`
}

function seleccionarImagen (file) {
  if (!file) return
  if (imagenOrigen.value.startsWith('blob:')) URL.revokeObjectURL(imagenOrigen.value)
  imagenNueva.value = file
  imagenOrigen.value = URL.createObjectURL(file)
  imagenPreview.value = imagenOrigen.value
  cropX.value = { min: 0, max: 100 }
  cropY.value = { min: 0, max: 100 }
  cropSeleccionado.value = false
  cropDialog.value = true
}

function abrirRecorte () {
  if (!imagenOrigen.value) imagenOrigen.value = imagenPreview.value
  cropX.value = { min: 0, max: 100 }
  cropY.value = { min: 0, max: 100 }
  cropSeleccionado.value = false
  cropDialog.value = true
}

async function aplicarRecorte () {
  const image = await cargarImagen(imagenOrigen.value)
  const sx = Math.round(image.naturalWidth * cropX.value.min / 100)
  const sy = Math.round(image.naturalHeight * cropY.value.min / 100)
  const sw = Math.max(1, Math.round(image.naturalWidth * (cropX.value.max - cropX.value.min) / 100))
  const sh = Math.max(1, Math.round(image.naturalHeight * (cropY.value.max - cropY.value.min) / 100))
  const scale = Math.min(1, 1800 / sw, 2400 / sh)
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(sw * scale)
  canvas.height = Math.round(sh * scale)
  canvas.getContext('2d').drawImage(image, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height)
  const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9))
  imagenNueva.value = new File([blob], `derivacion_${Date.now()}.jpg`, { type: 'image/jpeg' })
  if (imagenPreview.value.startsWith('blob:')) URL.revokeObjectURL(imagenPreview.value)
  imagenPreview.value = URL.createObjectURL(blob)
  cropDialog.value = false
}

function cargarImagen (src) {
  return new Promise((resolve, reject) => {
    const image = new Image()
    image.onload = () => resolve(image)
    image.onerror = reject
    image.src = src
  })
}

function puntoRecorte (event, canvas) {
  const rect = canvas.getBoundingClientRect()
  return {
    x: Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100)),
    y: Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100)),
    width: rect.width,
    height: rect.height,
  }
}

function iniciarCreacionRecorte (event) {
  if (event.button !== undefined && event.button !== 0) return
  const canvas = event.currentTarget
  const punto = puntoRecorte(event, canvas)
  draggingCrop.value = true
  cropDragMode.value = 'crear'
  cropSeleccionado.value = true
  dragStart.value = {
    x: event.clientX,
    y: event.clientY,
    width: punto.width,
    height: punto.height,
    puntoX: punto.x,
    puntoY: punto.y,
  }
  cropX.value = { min: punto.x, max: punto.x }
  cropY.value = { min: punto.y, max: punto.y }
  canvas.setPointerCapture?.(event.pointerId)
}

function iniciarMovimientoRecorte (event) {
  iniciarInteraccionRecorte(event, 'mover')
}

function iniciarRedimensionRecorte (event, handle) {
  resizeHandle.value = handle
  iniciarInteraccionRecorte(event, 'redimensionar')
}

function iniciarInteraccionRecorte (event, mode) {
  if (event.button !== undefined && event.button !== 0) return
  const canvas = event.currentTarget.closest('.crop-canvas')
  const rect = canvas.getBoundingClientRect()
  draggingCrop.value = true
  cropDragMode.value = mode
  dragStart.value = {
    x: event.clientX,
    y: event.clientY,
    width: rect.width,
    height: rect.height,
    cropX: { ...cropX.value },
    cropY: { ...cropY.value },
  }
  canvas.setPointerCapture?.(event.pointerId)
}

function moverRecorte (event) {
  if (!draggingCrop.value || !dragStart.value) return
  const start = dragStart.value
  const deltaX = ((event.clientX - start.x) / start.width) * 100
  const deltaY = ((event.clientY - start.y) / start.height) * 100
  if (cropDragMode.value === 'crear') {
    const actualX = Math.max(0, Math.min(100, start.puntoX + deltaX))
    const actualY = Math.max(0, Math.min(100, start.puntoY + deltaY))
    cropX.value = { min: Math.min(start.puntoX, actualX), max: Math.max(start.puntoX, actualX) }
    cropY.value = { min: Math.min(start.puntoY, actualY), max: Math.max(start.puntoY, actualY) }
    return
  }
  if (cropDragMode.value === 'redimensionar') {
    redimensionarRecorte(start, deltaX, deltaY)
    return
  }
  cropX.value = desplazarRango(start.cropX, deltaX)
  cropY.value = desplazarRango(start.cropY, deltaY)
}

function redimensionarRecorte (start, deltaX, deltaY) {
  const handle = resizeHandle.value
  const minSize = 5
  const x = { ...start.cropX }
  const y = { ...start.cropY }
  if (handle.includes('w')) x.min = Math.max(0, Math.min(x.max - minSize, start.cropX.min + deltaX))
  if (handle.includes('e')) x.max = Math.min(100, Math.max(x.min + minSize, start.cropX.max + deltaX))
  if (handle.includes('n')) y.min = Math.max(0, Math.min(y.max - minSize, start.cropY.min + deltaY))
  if (handle.includes('s')) y.max = Math.min(100, Math.max(y.min + minSize, start.cropY.max + deltaY))
  cropX.value = { min: Math.round(x.min * 10) / 10, max: Math.round(x.max * 10) / 10 }
  cropY.value = { min: Math.round(y.min * 10) / 10, max: Math.round(y.max * 10) / 10 }
}

function desplazarRango (range, delta) {
  const size = range.max - range.min
  const min = Math.max(0, Math.min(100 - size, range.min + delta))
  return { min: Math.round(min * 10) / 10, max: Math.round((min + size) * 10) / 10 }
}

function finalizarArrastreRecorte (event) {
  const canvas = event.currentTarget.closest?.('.crop-canvas') || event.currentTarget
  canvas?.releasePointerCapture?.(event.pointerId)
  if ((cropX.value.max - cropX.value.min) < 2 || (cropY.value.max - cropY.value.min) < 2) {
    cropX.value = { min: Math.max(0, cropX.value.min - 10), max: Math.min(100, cropX.value.min + 10) }
    cropY.value = { min: Math.max(0, cropY.value.min - 10), max: Math.min(100, cropY.value.min + 10) }
  }
  draggingCrop.value = false
  dragStart.value = null
  resizeHandle.value = null
  cropDragMode.value = null
}

function quitarImagen () {
  if (imagenPreview.value.startsWith('blob:')) URL.revokeObjectURL(imagenPreview.value)
  archivoOrigen.value = null
  imagenOrigen.value = ''
  imagenPreview.value = ''
  imagenNueva.value = null
}

async function guardar () {
  if (!form.value.id && !imagenNueva.value) {
    proxy.$alert.error('Seleccione la imagen de la derivación')
    return
  }
  guardando.value = true
  try {
    const payload = new FormData()
    for (const field of ['fecha', 'paciente', 'laboratorio_destino', 'servicio', 'observaciones']) {
      payload.append(field, form.value[field] || '')
    }
    if (imagenNueva.value) payload.append('imagen', imagenNueva.value)
    if (form.value.id) await proxy.$axios.post(`derivaciones/${form.value.id}`, payload)
    else await proxy.$axios.post('derivaciones', payload)
    proxy.$alert.success(form.value.id ? 'Derivación actualizada' : 'Derivación creada')
    limpiarFormulario()
    tab.value = 'registros'
    recargar()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo guardar la derivación')
  } finally {
    guardando.value = false
  }
}

async function editar (row) {
  form.value = {
    id: row.id,
    fecha: row.fecha,
    paciente: row.paciente,
    laboratorio_destino: row.laboratorio_destino || '',
    servicio: row.servicio || '',
    observaciones: row.observaciones || '',
  }
  archivoOrigen.value = null
  imagenNueva.value = null
  try {
    const response = await proxy.$axios.get(`derivaciones/${row.id}/imagen`, { responseType: 'blob' })
    imagenNueva.value = new File([response.data], `derivacion_${row.id}.jpg`, { type: response.data.type || 'image/jpeg' })
    imagenOrigen.value = URL.createObjectURL(response.data)
    imagenPreview.value = imagenOrigen.value
  } catch (error) {
    imagenOrigen.value = ''
    imagenPreview.value = imagenUrl(row)
  }
  cropX.value = { min: 0, max: 100 }
  cropY.value = { min: 0, max: 100 }
  tab.value = 'formulario'
}

function limpiarFormulario () {
  quitarImagen()
  form.value = formVacio()
}

function recargar () {
  tableRef.value?.requestServerInteraction()
}

function onRequest ({ pagination: requested }) {
  cargar(requested)
}

async function cargar (requested = pagination.value) {
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('derivaciones', {
      params: { ...filters.value, page: requested.page, per_page: requested.rowsPerPage },
    })
    rows.value = data.data
    pagination.value = { page: data.current_page, rowsPerPage: data.per_page, rowsNumber: data.total }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las derivaciones')
  } finally {
    loading.value = false
  }
}

async function cargarDatos () {
  try {
    const { data } = await proxy.$axios.get('derivaciones/form-data')
    usuarios.value = data.usuarios || []
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los usuarios')
  }
}

function imprimir (row) {
  const ventana = window.open('', '_blank')
  proxy.$axios.get(`derivaciones/${row.id}/pdf`, { responseType: 'blob' }).then(response => {
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (ventana) ventana.location.href = url
    else window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  }).catch(error => {
    ventana?.close()
    proxy.$alert.error(error.response?.data?.message || 'No se pudo generar la impresión')
  })
}

function eliminar (row) {
  proxy.$alert.dialog(`¿Eliminar la derivación de ${row.paciente}?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`derivaciones/${row.id}`)
      proxy.$alert.success('Derivación eliminada')
      recargar()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar la derivación')
    }
  })
}

function verImagen (row) {
  imagenSeleccionada.value = row
  imagenDialog.value = true
}

cargarDatos()
cargar()
</script>

<style scoped>
.preview-wrap { min-height: 330px; text-align: center; }
.preview-wrap img { max-width: 100%; max-height: 420px; object-fit: contain; border: 1px solid #d7dfdb; border-radius: 8px; }
.drop-empty { min-height: 330px; border: 2px dashed #cfd8d3; border-radius: 10px; background: #f8faf9; }
.crop-stage { max-height: 520px; overflow: auto; text-align: center; background: #111; line-height: 0; touch-action: none; user-select: none; }
.crop-stage--dragging { cursor: grabbing; }
.crop-canvas { position: relative; display: inline-block; max-width: 100%; cursor: crosshair; }
.crop-canvas img { display: block; max-width: 100%; max-height: 520px; }
.crop-mask { position: absolute; background: rgba(0, 0, 0, .58); pointer-events: none; }
.crop-mask--top { top: 0; left: 0; right: 0; }
.crop-mask--bottom { bottom: 0; left: 0; right: 0; }
.crop-mask--left { left: 0; }
.crop-mask--right { right: 0; }
.crop-frame { position: absolute; border: 2px solid #19b88a; box-shadow: 0 0 0 1px #fff; cursor: move; }
.crop-handle { position: absolute; width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; background: #19b88a; pointer-events: auto; }
.crop-handle--nw { top: -9px; left: -9px; cursor: nwse-resize; }
.crop-handle--ne { top: -9px; right: -9px; cursor: nesw-resize; }
.crop-handle--sw { bottom: -9px; left: -9px; cursor: nesw-resize; }
.crop-handle--se { right: -9px; bottom: -9px; cursor: nwse-resize; }
.imagen-completa { max-width: 100%; max-height: 78vh; object-fit: contain; }
.derivaciones-page :deep(.q-field--dense .q-field__control),
.derivaciones-page :deep(.q-field--dense .q-field__marginal) { min-height: 36px; }
</style>
