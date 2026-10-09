<template>
  <q-page class="q-pa-sm prod-farmacia">
    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver los productos de farmacia</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Productos</div>
            <div class="text-h6 text-teal text-weight-bold">{{ resumen.productos }}</div>
          </q-card>
        </div>
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Con stock</div>
            <div class="text-h6 text-green-8 text-weight-bold">{{ resumen.con_stock }}</div>
          </q-card>
        </div>
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Sin stock</div>
            <div class="text-h6 text-red-7 text-weight-bold">{{ resumen.sin_stock }}</div>
          </q-card>
        </div>
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Valor inventario (Bs.)</div>
            <div class="text-h6 text-indigo text-weight-bold">{{ money(resumen.valor_inventario) }}</div>
          </q-card>
        </div>
      </div>

      <div class="row items-center no-wrap q-gutter-xs q-mb-xs">
        <div>
          <div class="text-subtitle1 text-weight-bold">Productos de farmacia</div>
          <div class="prod-sub text-grey-6">Medicamentos e insumos del tipo FARMACIA</div>
        </div>
        <q-space />
        <q-input v-model="search" dense outlined clearable debounce="400" style="width:240px"
                 placeholder="Buscar por nombre, comercial, código o marca" @update:model-value="buscar">
          <template #prepend><q-icon name="search" size="16px" /></template>
        </q-input>
        <q-badge color="primary">{{ pagination.rowsNumber }}</q-badge>
        <q-btn v-if="canCrear" dense unelevated rounded color="primary" icon="add" label="Nuevo producto"
               no-caps size="11px" @click="nuevo" />
        <q-btn dense unelevated rounded color="red-7" icon="picture_as_pdf" no-caps size="11px"
               :loading="exportandoPdf" @click="exportarPdf">
          <q-tooltip>Exportar PDF</q-tooltip>
        </q-btn>
        <q-btn dense unelevated rounded color="green-8" icon="table_view" no-caps size="11px"
               :loading="exportandoExcel" @click="exportarExcel">
          <q-tooltip>Exportar Excel</q-tooltip>
        </q-btn>
        <q-btn flat round dense color="primary" icon="refresh" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <q-card flat bordered>
        <q-table v-model:pagination="pagination" flat dense row-key="id"
                 class="tabla-compacta"
                 :rows="productos" :columns="columns" :loading="loading"
                 :rows-per-page-options="[10, 20, 50, 100]"
                 no-data-label="No existen productos de farmacia con ese filtro"
                 @request="onRequest">
          <template #body-cell-acciones="props">
            <q-td :props="props">
              <q-btn-dropdown dense unelevated rounded color="primary" label="Opciones" no-caps size="10px">
                <q-list dense style="min-width:230px">
                  <q-item clickable v-close-popup @click="verHistorial(props.row)">
                    <q-item-section avatar><q-icon name="history" color="teal" /></q-item-section>
                    <q-item-section><q-item-label>Historial de compras y ventas</q-item-label></q-item-section>
                  </q-item>
                  <q-separator v-if="canEditar || canEliminar" />
                  <q-item v-if="canEditar" clickable v-close-popup @click="editar(props.row)">
                    <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                    <q-item-section><q-item-label>Modificar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="eliminar(props.row)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
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
              <div v-if="props.row.descripcion" class="prod-sub text-grey-6">{{ props.row.descripcion }}</div>
            </q-td>
          </template>
          <template #body-cell-nombre_comercial="props">
            <q-td :props="props">
              <span v-if="props.row.nombre_comercial" class="text-indigo-8">{{ props.row.nombre_comercial }}</span>
              <span v-else class="text-grey-5">—</span>
            </q-td>
          </template>
          <template #body-cell-unidad="props">
            <q-td :props="props">
              {{ props.row.unidad ? (props.row.unidad.abreviatura || props.row.unidad.nombre) : '—' }}
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
          <template #body-cell-stock="props">
            <q-td :props="props">
              <span :class="Number(props.row.stock) > 0 ? 'text-green-8 text-weight-bold' : 'text-grey-5'">
                {{ Number(props.row.stock || 0).toFixed(0) }}
              </span>
            </q-td>
          </template>
        </q-table>
      </q-card>
    </template>

    <!-- Historial de compras y ventas -->
    <q-dialog v-model="dialogHistorial">
      <q-card style="width:min(96vw,1000px);max-width:1000px">
        <q-card-section class="row items-center bg-teal text-white q-py-sm">
          <q-icon name="history" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">Historial de compras y ventas</div>
            <div class="text-caption">
              {{ historialProducto.codigo || 'SIN CÓDIGO' }} · {{ historialProducto.nombre }}
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-sm">
          <div class="row items-center q-gutter-sm q-mb-sm">
            <q-chip color="blue-1" text-color="blue-9" icon="shopping_cart" square>
              Comprado: <strong class="q-ml-xs">{{ money(cantidadComprada) }}</strong>
            </q-chip>
            <q-chip color="teal-1" text-color="teal-9" icon="point_of_sale" square>
              Vendido: <strong class="q-ml-xs">{{ money(cantidadVendida) }}</strong>
            </q-chip>
            <q-chip color="green-1" text-color="green-9" icon="inventory_2" square>
              Saldo: <strong class="q-ml-xs">{{ money(saldoHistorial) }}</strong>
            </q-chip>
          </div>

          <q-tabs v-model="tabHistorial" dense align="left" no-caps
                  active-color="primary" indicator-color="primary" class="text-grey-7">
            <q-tab name="compras" icon="shopping_cart"
                   :label="'Compras (' + comprasHistorial.length + ')'" />
            <q-tab name="ventas" icon="point_of_sale"
                   :label="'Ventas (' + ventasHistorial.length + ')'" />
          </q-tabs>
          <q-separator class="q-mb-sm" />

          <div class="tabla-wrap">
            <q-markup-table class="tabla-fija-sm tabla-compacta" dense flat bordered separator="cell">
              <thead>
                <tr class="bg-grey-2">
                  <th class="text-left">Fecha</th>
                  <th class="text-left">Documento</th>
                  <th class="text-left">{{ tabHistorial === 'compras' ? 'Proveedor' : 'Cliente' }}</th>
                  <th class="text-left">Lote</th>
                  <th class="text-left">Vencimiento</th>
                  <th class="text-right">{{ tabHistorial === 'compras' ? 'Comprada' : 'Cantidad' }}</th>
                  <th v-if="tabHistorial === 'compras'" class="text-right">Vendida</th>
                  <th v-if="tabHistorial === 'compras'" class="text-right">Saldo</th>
                  <th class="text-right">Precio (Bs.)</th>
                  <th class="text-right">Total (Bs.)</th>
                  <th class="text-center">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!movimientosTabHistorial.length && !loadingHistorial">
                  <td :colspan="tabHistorial === 'compras' ? 11 : 9" class="text-center text-grey-6 q-pa-lg">
                    No hay {{ tabHistorial }} registradas para este producto.
                  </td>
                </tr>
                <tr v-for="(mov, index) in movimientosTabHistorial" :key="mov.tipo + '-' + mov.id + '-' + index">
                  <td>{{ formatFechaHistorial(mov.fecha_hora) }}</td>
                  <td>{{ mov.documento }}</td>
                  <td>{{ mov.tercero }}</td>
                  <td>
                    {{ mov.lote || 'SIN LOTE' }}
                    <EditarLoteHistorial :producto="historialProducto" :movimiento="mov"
                                        @actualizado="movimientos = $event.movimientos" />
                  </td>
                  <td>{{ mov.fecha_vencimiento || 'SIN FECHA' }}</td>
                  <td class="text-right">{{ money(mov.cantidad) }}</td>
                  <td v-if="tabHistorial === 'compras'" class="text-right text-teal-8 text-weight-bold">
                    {{ money(mov.cantidad_vendida) }}
                  </td>
                  <td v-if="tabHistorial === 'compras'" class="text-right">
                    <q-badge :color="Number(mov.saldo) > 0 ? 'green-1' : 'red-1'"
                             :text-color="Number(mov.saldo) > 0 ? 'green-9' : 'negative'">
                      {{ money(mov.saldo) }}
                    </q-badge>
                  </td>
                  <td class="text-right">{{ money(mov.precio) }}</td>
                  <td class="text-right text-weight-bold">{{ money(mov.total) }}</td>
                  <td class="text-center">
                    <q-badge :color="mov.estado === 'ANULADO' ? 'negative' : (mov.estado === 'PENDIENTE' ? 'warning' : 'positive')">
                      {{ mov.estado }}
                    </q-badge>
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <q-inner-loading :showing="loadingHistorial" color="teal" />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Alta / edición -->
    <q-dialog v-model="dialog" persistent>
      <q-card style="width:min(96vw,640px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon :name="form.id ? 'edit' : 'add'" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">
            {{ form.id ? 'Modificar producto de farmacia' : 'Nuevo producto de farmacia' }}
          </span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardar">
          <q-card-section class="row q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <q-input v-model="form.codigo" v-uppercase dense outlined label="Código" />
            </div>
            <div class="col-12 col-sm-8">
              <q-input v-model="form.nombre" v-uppercase dense outlined label="Nombre genérico *" :rules="[required]" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="form.nombre_comercial" v-uppercase dense outlined label="Nombre comercial"
                       hint="Marca con la que se vende" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="form.marca" v-uppercase dense outlined label="Marca" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select v-model="form.fabricante_id" dense outlined clearable emit-value map-options
                        label="Fabricante" :options="opcionesFabricantes" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="form.unidad_id" dense outlined clearable emit-value map-options
                        label="Unidad de medida" :options="opcionesUnidades" />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model.number="form.precio" dense outlined type="number" min="0" step="0.01"
                       label="P. Normal (Bs) *" :rules="[required]" />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model.number="form.precio_seguro" dense outlined type="number" min="0" step="0.01"
                       label="P. Seguro (Bs)" hint="Precio de convenio" />
            </div>
            <div class="col-12">
              <q-input v-model="form.descripcion" v-uppercase dense outlined type="textarea" rows="2"
                       label="Descripción" />
            </div>
          </q-card-section>
          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat dense label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" dense padding="4px 14px" color="primary" icon="save" label="Guardar"
                   no-caps :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import EditarLoteHistorial from '../../../components/EditarLoteHistorial.vue'

const { proxy } = getCurrentInstance()

const productos = ref([])
const fabricantes = ref([])
const unidades = ref([])
const resumen = ref({ productos: 0, con_stock: 0, sin_stock: 0, valor_inventario: 0 })
const loading = ref(false)
const saving = ref(false)
const exportandoPdf = ref(false)
const exportandoExcel = ref(false)
const search = ref('')
const dialog = ref(false)
const form = ref({})
const pagination = ref({ page: 1, rowsPerPage: 20, rowsNumber: 0 })

// Historial de movimientos del producto
const dialogHistorial = ref(false)
const loadingHistorial = ref(false)
const historialProducto = ref({})
const movimientos = ref([])
const tabHistorial = ref('compras')

const columns = [
  { name: 'acciones', label: 'Opciones', field: 'id', align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'nombre', label: 'Genérico', field: 'nombre', align: 'left', sortable: true },
  { name: 'nombre_comercial', label: 'Comercial', field: row => row.nombre_comercial || '—', align: 'left', sortable: true },
  { name: 'marca', label: 'Marca', field: row => row.marca || '—', align: 'left' },
  { name: 'fabricante', label: 'Fabricante', field: row => row.fabricante?.nombre || '—', align: 'left' },
  { name: 'unidad', label: 'Unidad', field: row => row.unidad?.abreviatura, align: 'center' },
  { name: 'precio', label: 'P. Normal', field: 'precio', align: 'right', sortable: true },
  { name: 'precio_seguro', label: 'P. Seguro', field: 'precio_seguro', align: 'right', sortable: true },
  { name: 'stock', label: 'Stock', field: 'stock', align: 'right' },
]

// Permisos propios: no dependen de los del catálogo general de /farmacia.
const canVer = computed(() => proxy.$store.hasPermission('Ver Productos Farmacia'))
const canCrear = computed(() => proxy.$store.hasPermission('Crear Productos Farmacia'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Productos Farmacia'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos Farmacia'))

const comprasHistorial = computed(() => movimientos.value.filter(mov => mov.tipo === 'COMPRA'))
const ventasHistorial = computed(() => movimientos.value.filter(mov => mov.tipo === 'VENTA'))
const movimientosTabHistorial = computed(() => (
  tabHistorial.value === 'compras' ? comprasHistorial.value : ventasHistorial.value
))
// Los movimientos anulados no cuentan para el saldo.
const cantidadComprada = computed(() => comprasHistorial.value
  .filter(mov => mov.estado === 'ACTIVO')
  .reduce((total, mov) => total + Number(mov.cantidad || 0), 0))
const cantidadVendida = computed(() => ventasHistorial.value
  .filter(mov => mov.estado === 'ACTIVO')
  .reduce((total, mov) => total + Number(mov.cantidad || 0), 0))
const saldoHistorial = computed(() => cantidadComprada.value - cantidadVendida.value)

const opcionesFabricantes = computed(() => fabricantes.value.map(f => ({ label: f.nombre, value: f.id })))
const opcionesUnidades = computed(() => unidades.value.map(u => ({
  label: u.abreviatura ? `${u.nombre} (${u.abreviatura})` : u.nombre,
  value: u.id,
})))

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) {
    cargarCatalogos()
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

async function cargar () {
  if (!canVer.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('productos-farmacia', {
      params: {
        q: search.value || undefined,
        page: pagination.value.page,
        per_page: pagination.value.rowsPerPage,
      },
    })
    productos.value = data.data || []
    pagination.value = {
      ...pagination.value,
      page: data.current_page || 1,
      rowsPerPage: data.per_page || 20,
      rowsNumber: data.total || 0,
    }
    cargarResumen()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los productos')
  } finally {
    loading.value = false
  }
}

async function cargarResumen () {
  try {
    const { data } = await proxy.$axios.get('productos-farmacia/resumen')
    resumen.value = data
  } catch {
    // El resumen es informativo: si falla, la tabla sigue siendo usable.
  }
}

async function cargarCatalogos () {
  try {
    const { data } = await proxy.$axios.get('productos-farmacia/catalogos')
    fabricantes.value = data.fabricantes || []
    unidades.value = data.unidades || []
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar fabricantes y unidades')
  }
}

function formatFechaHistorial (fecha) {
  if (!fecha) return '—'
  return new Intl.DateTimeFormat('es-BO', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(fecha))
}

async function verHistorial (producto) {
  historialProducto.value = producto
  movimientos.value = []
  tabHistorial.value = 'compras'
  dialogHistorial.value = true
  loadingHistorial.value = true
  try {
    const { data } = await proxy.$axios.get('productos-farmacia/' + producto.id + '/historial')
    historialProducto.value = data.producto || producto
    movimientos.value = data.movimientos || []
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar el historial')
  } finally {
    loadingHistorial.value = false
  }
}

function nuevo () {
  form.value = {
    codigo: '', nombre: '', nombre_comercial: '', descripcion: '', marca: '',
    fabricante_id: null, unidad_id: null, precio: 0, precio_seguro: null,
  }
  dialog.value = true
}

function editar (producto) {
  form.value = {
    ...producto,
    precio: Number(producto.precio),
    precio_seguro: producto.precio_seguro !== null && producto.precio_seguro !== undefined
      ? Number(producto.precio_seguro)
      : null,
    fabricante_id: producto.fabricante?.id || null,
    unidad_id: producto.unidad?.id || null,
  }
  dialog.value = true
}

async function guardar () {
  saving.value = true
  try {
    if (form.value.id) {
      await proxy.$axios.put('productos-farmacia/' + form.value.id, form.value)
      proxy.$alert.success('Producto actualizado')
    } else {
      await proxy.$axios.post('productos-farmacia', form.value)
      proxy.$alert.success('Producto creado')
    }
    dialog.value = false
    await cargar()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo guardar el producto')
  } finally {
    saving.value = false
  }
}

function eliminar (producto) {
  proxy.$alert.dialog(`¿Eliminar el producto "${producto.nombre}"?`).onOk(async () => {
    try {
      await proxy.$axios.delete('productos-farmacia/' + producto.id)
      proxy.$alert.success('Producto eliminado')
      await cargar()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar el producto')
    }
  })
}

async function exportarPdf () {
  exportandoPdf.value = true
  try {
    const res = await proxy.$axios.get('productos-farmacia/export-pdf', {
      params: { q: search.value || undefined },
      responseType: 'blob',
    })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch {
    proxy.$alert.error('Error al generar el PDF')
  } finally {
    exportandoPdf.value = false
  }
}

async function exportarExcel () {
  exportandoExcel.value = true
  try {
    const res = await proxy.$axios.get('productos-farmacia/export-excel', {
      params: { q: search.value || undefined },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = 'productos_farmacia_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch {
    proxy.$alert.error('Error al generar el Excel')
  } finally {
    exportandoExcel.value = false
  }
}
</script>

<style scoped>
/* El spinner del historial se superpone en vez de reemplazar las filas */
.tabla-wrap {
  position: relative;
}

.tabla-fija-sm {
  height: 46vh;
  min-height: 220px;
}

.tabla-fija-sm :deep(thead tr th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background-color: #eeeeee;
}

.prod-sub {
  font-size: 10px;
  line-height: 1.3;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 2px 8px;
}

.prod-farmacia :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.prod-farmacia :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.prod-farmacia :deep(.q-field--dense .q-field__native),
.prod-farmacia :deep(.q-field--dense .q-field__input),
.prod-farmacia :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
.prod-farmacia :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}
</style>
