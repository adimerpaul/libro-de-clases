<template>
  <q-page class="q-pa-sm lab-lista">
    <div v-if="proxy.$store.isLogged && !canVer" class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center no-wrap q-gutter-xs q-mb-xs">
        <div>
          <div class="text-subtitle1 text-weight-bold">Laboratorio</div>
          <div class="lab-sub text-grey-6">Exámenes por área y configuración de resultados</div>
        </div>
        <q-space />
        <q-select v-model="areaFiltro" dense outlined clearable emit-value map-options
                  style="width:190px" label="Área" :options="opcionesAreas"
                  @update:model-value="buscar" />
        <q-input v-model="search" dense outlined clearable debounce="400" style="width:230px"
                 placeholder="Buscar por nombre o código" @update:model-value="buscar">
          <template #prepend><q-icon name="search" size="16px" /></template>
        </q-input>
        <q-badge color="primary">{{ pagination.rowsNumber }}</q-badge>
        <q-btn v-if="canCrear" dense unelevated rounded color="primary" icon="add" label="Nuevo examen"
               no-caps size="11px" @click="nuevoProducto" />
        <q-btn dense outline rounded color="primary" icon="category" label="Áreas"
               no-caps size="11px" @click="abrirAreas" />
        <q-btn flat round dense color="primary" icon="refresh" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <!-- Accesos rápidos por área -->
      <div class="row items-center q-gutter-xs q-mb-xs">
        <q-chip :selected="areaFiltro === null" clickable dense size="11px"
                :color="areaFiltro === null ? 'primary' : 'grey-3'"
                :text-color="areaFiltro === null ? 'white' : 'grey-8'"
                @click="seleccionarArea(null)">
          Todas ({{ totalAreas }})
        </q-chip>
        <q-chip v-for="area in areas" :key="area.id" clickable dense size="11px"
                :color="areaFiltro === area.id ? area.color : 'grey-3'"
                :text-color="areaFiltro === area.id ? 'white' : 'grey-8'"
                @click="seleccionarArea(area.id)">
          {{ area.nombre }} ({{ area.productos_count }})
        </q-chip>
      </div>

      <q-card flat bordered>
        <q-table v-model:pagination="pagination" flat dense row-key="id"
                 class="tabla-compacta"
                 :rows="productos" :columns="columns" :loading="loading"
                 :rows-per-page-options="[10, 20, 50, 100]"
                 no-data-label="No existen exámenes de laboratorio con ese filtro"
                 @request="onRequest">
          <template #body-cell-acciones="props">
            <q-td :props="props">
              <q-btn-dropdown dense unelevated rounded color="primary" label="Opciones" no-caps size="10px">
                <q-list dense style="min-width:180px">
                  <q-item v-if="canEditar" clickable v-close-popup @click="editarProducto(props.row)">
                    <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                    <q-item-section><q-item-label>Modificar</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup :to="`/laboratorio/${props.row.id}`">
                    <q-item-section avatar><q-icon name="settings" color="teal" /></q-item-section>
                    <q-item-section>
                      <q-item-label>Administrar</q-item-label>
                      <q-item-label caption>Datos, rangos y fórmulas</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
          <template #body-cell-codigo="props">
            <q-td :props="props"><q-badge outline color="primary">{{ props.row.codigo || 'S/C' }}</q-badge></q-td>
          </template>
          <template #body-cell-nombre="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.nombre }}</div>
              <div v-if="props.row.descripcion" class="lab-sub text-grey-6" v-html="props.row.descripcion_html" />
            </q-td>
          </template>
          <template #body-cell-tipo="props">
            <q-td :props="props">
              <q-badge :color="props.row.tipo_producto?.color || 'primary'">
                {{ props.row.tipo_producto?.nombre || 'SIN ÁREA' }}
              </q-badge>
            </q-td>
          </template>
          <template #body-cell-precio="props">
            <q-td :props="props" class="text-weight-bold">Bs {{ money(props.row.precio) }}</q-td>
          </template>
          <template #body-cell-precio_seguro="props">
            <q-td :props="props">
              <span v-if="props.row.precio_seguro !== null && props.row.precio_seguro !== undefined">
                Bs {{ money(props.row.precio_seguro) }}
              </span>
              <span v-else class="text-grey-5">—</span>
            </q-td>
          </template>
        </q-table>
      </q-card>
    </template>

    <q-dialog v-model="productoDialog" persistent>
      <q-card style="width:min(94vw,620px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon :name="productoForm.id ? 'edit' : 'add'" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">
            {{ productoForm.id ? 'Modificar examen de laboratorio' : 'Nuevo examen de laboratorio' }}
          </span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarProducto">
          <q-card-section class="row q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <q-input v-model="productoForm.codigo" v-uppercase dense outlined label="Código" />
            </div>
            <div class="col-12 col-sm-8">
              <q-input v-model="productoForm.nombre" v-uppercase dense outlined label="Nombre *" :rules="[required]" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="productoForm.tipo_producto_id" dense outlined emit-value map-options
                        label="Área *" :options="opcionesAreas" :rules="[required]" />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model.number="productoForm.precio" dense outlined type="number" min="0" step="0.01"
                       label="P. Normal (Bs) *" :rules="[required]" />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model.number="productoForm.precio_seguro" dense outlined type="number" min="0" step="0.01"
                       label="P. Seguro (Bs)" hint="Precio de convenio" />
            </div>
            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">Descripción</div>
              <q-editor v-model="productoForm.descripcion" min-height="140px"
                        :toolbar="[['bold', 'italic', 'underline', 'strike'], ['left', 'center', 'right', 'justify'], ['unordered', 'ordered'], ['undo', 'redo']]" />
            </div>
          </q-card-section>
          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat dense label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" dense padding="4px 14px" color="primary" icon="save" label="Guardar" no-caps :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- ── Administración de áreas de laboratorio ─────────────────── -->
    <q-dialog v-model="areasDialog">
      <q-card style="width:min(94vw,720px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="category" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Áreas de laboratorio</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-sm">
          <q-form v-if="canCrear || canEditar" class="row items-start q-col-gutter-xs q-mb-sm"
                  @submit.prevent="guardarArea">
            <div class="col-12 col-sm-5">
              <q-input v-model="areaForm.nombre" v-uppercase dense outlined label="Nombre del área *" :rules="[required]" />
            </div>
            <div class="col-8 col-sm-4">
              <q-select v-model="areaForm.color" dense outlined emit-value map-options
                        label="Color" :options="opcionesColores">
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section avatar><q-badge :color="scope.opt.value" /></q-item-section>
                    <q-item-section>{{ scope.opt.label }}</q-item-section>
                  </q-item>
                </template>
                <template #selected>
                  <q-badge v-if="areaForm.color" :color="areaForm.color" class="q-mr-xs" />
                  <span class="lab-sub">{{ areaForm.color || 'Sin color' }}</span>
                </template>
              </q-select>
            </div>
            <div class="col-4 col-sm-3">
              <q-btn type="submit" dense unelevated color="primary" class="full-width"
                     :icon="areaForm.id ? 'save' : 'add'" :label="areaForm.id ? 'Guardar' : 'Agregar'"
                     no-caps size="11px" :loading="savingArea" />
              <q-btn v-if="areaForm.id" flat dense class="full-width q-mt-xs" label="Cancelar edición"
                     no-caps size="10px" @click="limpiarArea" />
            </div>
          </q-form>

          <q-markup-table flat dense class="tabla-compacta">
            <thead>
              <tr>
                <th class="text-left">Área</th>
                <th class="text-center">Exámenes</th>
                <th class="text-right">Opciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="area in areas" :key="area.id">
                <td><q-badge :color="area.color || 'primary'">{{ area.nombre }}</q-badge></td>
                <td class="text-center">{{ area.productos_count }}</td>
                <td class="text-right">
                  <q-btn v-if="canEditar" flat round dense size="10px" icon="edit" color="primary"
                         @click="editarArea(area)">
                    <q-tooltip>Modificar</q-tooltip>
                  </q-btn>
                  <q-btn v-if="canEliminar" flat round dense size="10px" icon="delete" color="negative"
                         :disable="area.productos_count > 0" @click="eliminarArea(area)">
                    <q-tooltip>
                      {{ area.productos_count > 0 ? 'Tiene exámenes asignados' : 'Eliminar' }}
                    </q-tooltip>
                  </q-btn>
                </td>
              </tr>
              <tr v-if="!areas.length">
                <td colspan="3" class="text-center text-grey-6">Sin áreas registradas</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

const productos = ref([])
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const productoDialog = ref(false)
const productoForm = ref({})
const pagination = ref({ page: 1, rowsPerPage: 20, rowsNumber: 0 })

/* Áreas de laboratorio: son tipos de producto marcados con es_laboratorio,
   así conviven con FARMACIA, ECOGRAFIA y demás en el mismo catálogo. */
const areas = ref([])
const areaFiltro = ref(null)
const areasDialog = ref(false)
const areaForm = ref({ nombre: '', color: 'primary' })
const savingArea = ref(false)

const opcionesColores = [
  { label: 'Rojo', value: 'red-8' }, { label: 'Azul', value: 'blue-8' },
  { label: 'Ámbar', value: 'amber-8' }, { label: 'Verde', value: 'green-8' },
  { label: 'Morado', value: 'purple-8' }, { label: 'Turquesa', value: 'teal-8' },
  { label: 'Naranja', value: 'deep-orange-8' }, { label: 'Rosa', value: 'pink-8' },
  { label: 'Índigo', value: 'indigo-8' }, { label: 'Café', value: 'brown-8' },
  { label: 'Cian', value: 'cyan-8' }, { label: 'Lima', value: 'lime-8' },
]

const columns = [
  { name: 'acciones', label: 'Opciones', field: 'id', align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'nombre', label: 'Producto', field: 'nombre', align: 'left', sortable: true },
  { name: 'tipo', label: 'Área', field: row => row.tipo_producto?.nombre, align: 'center' },
  { name: 'precio', label: 'P. Normal', field: 'precio', align: 'right', sortable: true },
  { name: 'precio_seguro', label: 'P. Seguro', field: 'precio_seguro', align: 'right', sortable: true },
]

const canVer = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canCrear = computed(() => proxy.$store.hasPermission('Crear Productos'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Productos'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos'))

const opcionesAreas = computed(() => areas.value.map(a => ({ label: a.nombre, value: a.id })))
const totalAreas = computed(() => areas.value.reduce((suma, a) => suma + (a.productos_count || 0), 0))

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) {
    cargarAreas()
    cargar()
  }
}, { immediate: true })

function required (value) {
  return value !== null && value !== undefined && value !== '' || 'Campo requerido'
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
function buscar () {
  pagination.value.page = 1
  cargar()
}
function onRequest ({ pagination: requested }) {
  pagination.value = requested
  cargar()
}

function seleccionarArea (id) {
  areaFiltro.value = id
  buscar()
}

async function cargar () {
  if (!canVer.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('productos', {
      params: {
        laboratorio: 1,
        tipo_producto_id: areaFiltro.value || undefined,
        q: search.value || undefined,
        page: pagination.value.page,
        per_page: pagination.value.rowsPerPage,
      },
    })
    productos.value = data.data || []
    pagination.value = { ...pagination.value, page: data.current_page || 1, rowsPerPage: data.per_page || 20, rowsNumber: data.total || 0 }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los productos')
  } finally {
    loading.value = false
  }
}

async function cargarAreas () {
  if (!canVer.value) return
  try {
    const { data } = await proxy.$axios.get('tipo-productos', { params: { laboratorio: 1 } })
    areas.value = data
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las áreas')
  }
}

function nuevoProducto () {
  productoForm.value = {
    codigo: '', nombre: '', descripcion: '', precio: 0, precio_seguro: null,
    // Arranca en el área que se está viendo; si son todas, en la primera.
    tipo_producto_id: areaFiltro.value || areas.value[0]?.id || null,
  }
  productoDialog.value = true
}
function editarProducto (producto) {
  productoForm.value = {
    ...producto,
    descripcion: producto.descripcion_html || '',
    precio: Number(producto.precio),
    precio_seguro: producto.precio_seguro !== null && producto.precio_seguro !== undefined
      ? Number(producto.precio_seguro)
      : null,
    fabricante_id: producto.fabricante?.id || null,
    unidad_id: producto.unidad?.id || null,
    tipo_producto_id: producto.tipo_producto?.id || null,
  }
  productoDialog.value = true
}
async function guardarProducto () {
  saving.value = true
  try {
    if (productoForm.value.id) {
      await proxy.$axios.put('productos/' + productoForm.value.id, productoForm.value)
      proxy.$alert.success('Examen actualizado')
    } else {
      await proxy.$axios.post('productos', productoForm.value)
      proxy.$alert.success('Examen creado')
    }
    productoDialog.value = false
    await Promise.all([cargar(), cargarAreas()])
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}

/* ── Áreas ─────────────────────────────────────────────────────── */
function abrirAreas () {
  limpiarArea()
  areasDialog.value = true
  cargarAreas()
}
function limpiarArea () {
  areaForm.value = { nombre: '', color: 'primary' }
}
function editarArea (area) {
  areaForm.value = { id: area.id, nombre: area.nombre, color: area.color || 'primary' }
}
async function guardarArea () {
  savingArea.value = true
  try {
    // es_laboratorio marca el área como propia del laboratorio: sin esto no
    // aparecería en esta pantalla.
    const cuerpo = { ...areaForm.value, es_laboratorio: true }
    if (areaForm.value.id) {
      await proxy.$axios.put('tipo-productos/' + areaForm.value.id, cuerpo)
      proxy.$alert.success('Área actualizada')
    } else {
      await proxy.$axios.post('tipo-productos', cuerpo)
      proxy.$alert.success('Área creada')
    }
    limpiarArea()
    await Promise.all([cargarAreas(), cargar()])
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo guardar el área')
  } finally {
    savingArea.value = false
  }
}
function eliminarArea (area) {
  proxy.$alert.dialog(`¿Eliminar el área "${area.nombre}"?`).onOk(async () => {
    try {
      await proxy.$axios.delete('tipo-productos/' + area.id)
      proxy.$alert.success('Área eliminada')
      if (areaFiltro.value === area.id) areaFiltro.value = null
      await Promise.all([cargarAreas(), cargar()])
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar el área')
    }
  })
}
</script>

<style scoped>
.lab-sub {
  font-size: 10px;
  line-height: 1.3;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 2px 8px;
}

.lab-lista :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.lab-lista :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.lab-lista :deep(.q-field--dense .q-field__native),
.lab-lista :deep(.q-field--dense .q-field__input),
.lab-lista :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
.lab-lista :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}
</style>

