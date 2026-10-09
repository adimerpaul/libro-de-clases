<template>
  <q-page class="q-pa-md bajas-page">
    <div class="row items-center q-col-gutter-sm q-mb-md">
      <div class="col">
        <div class="text-h5 text-weight-bold">Bajas de farmacia</div>
        <div class="text-body2 text-grey-6">
          Salidas de inventario que no son ventas: vencimiento, cruce, bonificación, deterioro…
        </div>
      </div>
      <div class="col-auto">
        <q-btn round flat color="primary" icon="refresh" :loading="loading" @click="recargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>
      <div class="col-auto">
        <q-btn v-if="canCrear" color="negative" icon="remove_shopping_cart" label="Registrar baja" no-caps @click="abrirFormulario" />
      </div>
    </div>

    <!-- Resumen del mes -->
    <div class="row q-col-gutter-sm q-mb-md">
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="q-pa-sm rounded-borders">
          <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Bajas del mes</div>
          <div class="text-h5 text-weight-bold">{{ resumen.bajas_mes || 0 }}</div>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="q-pa-sm rounded-borders">
          <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Unidades dadas de baja</div>
          <div class="text-h5 text-weight-bold text-primary">{{ numero(resumen.unidades_mes) }}</div>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="q-pa-sm rounded-borders">
          <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Costo perdido (mes)</div>
          <div class="text-h5 text-weight-bold text-negative">{{ dinero(resumen.costo_mes) }}</div>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="rounded-borders">
      <q-card-section class="q-pa-sm">
        <div class="row items-center q-col-gutter-xs">
          <div class="col-12 col-sm">
            <q-input v-model="filtros.q" dense outlined clearable debounce="400"
                     placeholder="Buscar producto, lote, usuario u observación" @update:model-value="buscar">
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </div>
          <div class="col-6 col-sm-auto">
            <q-select v-model="filtros.motivo" dense outlined clearable emit-value map-options
                      :options="motivos" label="Motivo" style="width:190px" @update:model-value="buscar" />
          </div>
          <div class="col-6 col-sm-auto">
            <q-select v-model="filtros.estado" dense outlined clearable
                      :options="['ACTIVO', 'ANULADO']" label="Estado" style="width:130px" @update:model-value="buscar" />
          </div>
          <div class="col-6 col-sm-auto">
            <q-input v-model="filtros.desde" dense outlined clearable type="date" label="Desde"
                     style="width:165px" @update:model-value="buscar" />
          </div>
          <div class="col-6 col-sm-auto">
            <q-input v-model="filtros.hasta" dense outlined clearable type="date" label="Hasta"
                     style="width:165px" @update:model-value="buscar" />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-table flat dense row-key="id" :rows="rows" :columns="columns" :loading="loading"
               v-model:pagination="pagination" :rows-per-page-options="[10, 15, 25, 50]"
               no-data-label="No se registraron bajas" @request="onRequest">
        <template #body-cell-fecha="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ fechaHora(props.row.fecha_hora) }}</div>
            <div class="text-caption text-grey-7">
              {{ props.row.user?.name || props.row.user?.username || '—' }}
            </div>
          </q-td>
        </template>

        <template #body-cell-motivo="props">
          <q-td :props="props">
            <q-chip dense square color="deep-orange-1" text-color="deep-orange-9" class="text-weight-medium">
              {{ etiquetaMotivo(props.row.motivo) }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-productos="props">
          <q-td :props="props" class="productos-cell">
            <div v-for="detalle in props.row.detalles" :key="detalle.id" class="text-caption">
              <b>{{ numero(detalle.cantidad) }}</b> ×
              {{ detalle.nombre }}
              <span class="text-grey-7">
                · Lote {{ detalle.lote || 'S/L' }} · Vence {{ fecha(detalle.fecha_vencimiento) }}
              </span>
            </div>
            <div v-if="props.row.observacion" class="text-caption text-grey-6 q-mt-xs">
              {{ props.row.observacion }}
            </div>
          </q-td>
        </template>

        <template #body-cell-total="props">
          <q-td :props="props" class="text-right text-weight-bold">
            {{ dinero(props.row.total) }}
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge :color="props.row.estado === 'ACTIVO' ? 'positive' : 'grey'">{{ props.row.estado }}</q-badge>
            <div v-if="props.row.estado === 'ANULADO'" class="text-caption text-grey-7">
              {{ props.row.motivo_anulacion }}
            </div>
          </q-td>
        </template>

        <template #body-cell-opciones="props">
          <q-td :props="props">
            <q-btn v-if="canAnular && props.row.estado === 'ACTIVO'"
                   flat round dense icon="block" color="negative" @click="anular(props.row)">
              <q-tooltip>Anular y devolver el stock</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Formulario de nueva baja -->
    <q-dialog v-model="dialog" persistent>
      <q-card style="width:min(96vw,900px);max-width:900px">
        <q-card-section class="row items-center bg-negative text-white q-py-sm">
          <q-icon name="remove_shopping_cart" class="q-mr-sm" />
          <b>Registrar baja de inventario</b>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="guardar">
          <q-card-section class="q-pa-sm">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4">
                <q-select v-model="form.motivo" dense outlined emit-value map-options :options="motivos"
                          label="Motivo de la baja *" :rules="[requerido]" />
              </div>
              <div class="col-12 col-sm-8">
                <q-input v-model="form.observacion" dense outlined v-uppercase
                         label="Observación (opcional)" maxlength="500" />
              </div>
            </div>

            <div class="row items-center q-mt-md q-mb-xs">
              <b>Productos a dar de baja</b>
              <q-space />
              <q-btn flat dense no-caps color="primary" icon="add" label="Agregar producto" @click="agregarLinea" />
            </div>

            <q-list bordered separator>
              <q-item v-for="(linea, index) in form.detalles" :key="index" class="q-py-sm">
                <q-item-section>
                  <div class="row q-col-gutter-xs">
                    <div class="col-12 col-md-4">
                      <q-select
                        v-model="linea.producto_id"
                        dense outlined use-input emit-value map-options
                        label="Producto *"
                        :options="linea.opciones"
                        option-value="id" option-label="nombre"
                        :loading="linea.buscando"
                        @filter="(val, update, abort) => buscarProductos(linea, val, update, abort)"
                        @update:model-value="() => onProductoElegido(linea)"
                      >
                        <template #option="scope">
                          <q-item v-bind="scope.itemProps">
                            <q-item-section>
                              <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                              <q-item-label caption>
                                {{ scope.opt.codigo || 'S/C' }}
                                <span v-if="scope.opt.nombre_comercial"> · {{ scope.opt.nombre_comercial }}</span>
                              </q-item-label>
                            </q-item-section>
                          </q-item>
                        </template>
                      </q-select>
                    </div>

                    <div class="col-12 col-md-4">
                      <q-select
                        v-model="linea.compra_detalle_id"
                        dense outlined emit-value map-options
                        label="Lote / vencimiento *"
                        :options="linea.lotes"
                        option-value="compra_detalle_id"
                        :option-label="etiquetaLote"
                        :loading="linea.cargandoLotes"
                        :disable="!linea.producto_id"
                        :hint="linea.producto_id && !linea.cargandoLotes && !linea.lotes.length ? 'Sin lotes con saldo' : ''"
                        @update:model-value="() => onLoteElegido(linea)"
                      >
                        <template #option="scope">
                          <q-item v-bind="scope.itemProps">
                            <q-item-section>
                              <q-item-label>Lote {{ scope.opt.lote || 'S/L' }}</q-item-label>
                              <q-item-label caption>
                                Vence {{ fecha(scope.opt.fecha_vencimiento) }} ·
                                Disponible {{ numero(scope.opt.disponible) }}
                              </q-item-label>
                            </q-item-section>
                            <q-item-section side>
                              <q-badge :color="colorVencimiento(scope.opt.dias_vencimiento)">
                                {{ textoDias(scope.opt.dias_vencimiento) }}
                              </q-badge>
                            </q-item-section>
                          </q-item>
                        </template>
                      </q-select>
                    </div>

                    <div class="col-6 col-md-2">
                      <q-input v-model.number="linea.cantidad" dense outlined type="number"
                               min="0.0001" step="0.0001" label="Cantidad *"
                               :suffix="linea.unidad"
                               :error="linea.cantidad > linea.disponible"
                               :error-message="`Máximo ${numero(linea.disponible)}`" />
                    </div>

                    <div class="col-6 col-md-2">
                      <q-select v-model="linea.motivo" dense outlined clearable emit-value map-options
                                :options="motivos" label="Motivo línea"
                                hint="Vacío = motivo general" />
                    </div>
                  </div>
                </q-item-section>

                <q-item-section side top>
                  <q-btn flat round dense icon="delete" color="negative" @click="form.detalles.splice(index, 1)" />
                </q-item-section>
              </q-item>

              <q-item v-if="!form.detalles.length">
                <q-item-section class="text-grey-5 text-center">Agregue al menos un producto</q-item-section>
              </q-item>
            </q-list>

            <div class="row justify-end q-mt-sm">
              <div class="text-subtitle2">
                Costo estimado de la baja:
                <b class="text-negative">{{ dinero(totalFormulario) }}</b>
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" no-caps v-close-popup />
            <q-btn color="negative" label="Registrar baja" icon="save" no-caps type="submit"
                   :loading="saving" :disable="!puedeGuardar" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, reactive, ref } from 'vue'

const { proxy } = getCurrentInstance()

const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const rows = ref([])
const motivos = ref([])
const resumen = ref({})
const pagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })

const filtros = reactive({ q: '', motivo: null, estado: null, desde: '', hasta: '' })
const form = reactive({ motivo: null, observacion: '', detalles: [] })

const canCrear = computed(() => proxy.$store.hasPermission('Crear Bajas'))
const canAnular = computed(() => proxy.$store.hasPermission('Anular Bajas'))

const columns = [
  { name: 'opciones', label: '', field: 'id', align: 'left' },
  { name: 'fecha', label: 'Fecha / usuario', field: 'fecha_hora', align: 'left' },
  { name: 'motivo', label: 'Motivo', field: 'motivo', align: 'left' },
  { name: 'productos', label: 'Productos y lotes', field: 'detalles', align: 'left' },
  { name: 'total', label: 'Costo', field: 'total', align: 'right' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
]

const totalFormulario = computed(() => form.detalles.reduce(
  (total, linea) => total + Number(linea.cantidad || 0) * Number(linea.precio || 0), 0,
))

const puedeGuardar = computed(() =>
  !!form.motivo &&
  form.detalles.length > 0 &&
  form.detalles.every(linea =>
    linea.producto_id && linea.compra_detalle_id &&
    Number(linea.cantidad) > 0 && Number(linea.cantidad) <= Number(linea.disponible),
  ),
)

onMounted(() => {
  cargarCatalogos()
  cargar()
  cargarResumen()
})

// ── Datos ─────────────────────────────────────────────────────

async function cargarCatalogos () {
  try {
    const { data } = await proxy.$axios.get('/bajas/catalogos')
    motivos.value = data.motivos
  } catch { motivos.value = [] }
}

async function cargar () {
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('/bajas', {
      params: {
        q: filtros.q || undefined,
        motivo: filtros.motivo || undefined,
        estado: filtros.estado || undefined,
        desde: filtros.desde || undefined,
        hasta: filtros.hasta || undefined,
        page: pagination.value.page,
        per_page: pagination.value.rowsPerPage,
      },
    })
    rows.value = data.data || []
    pagination.value.rowsNumber = data.total || 0
    pagination.value.page = data.current_page || 1
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las bajas')
  } finally {
    loading.value = false
  }
}

async function cargarResumen () {
  try {
    const { data } = await proxy.$axios.get('/bajas/resumen')
    resumen.value = data
  } catch { resumen.value = {} }
}

function recargar () { cargar(); cargarResumen() }
function buscar () { pagination.value.page = 1; cargar() }
function onRequest (request) { pagination.value = request.pagination; cargar() }

// ── Formulario ────────────────────────────────────────────────

function abrirFormulario () {
  form.motivo = null
  form.observacion = ''
  form.detalles = []
  agregarLinea()
  dialog.value = true
}

function lineaVacia () {
  return {
    producto_id: null, compra_detalle_id: null, cantidad: null, motivo: null,
    opciones: [], lotes: [], buscando: false, cargandoLotes: false,
    disponible: 0, precio: 0, unidad: '',
  }
}

function agregarLinea () { form.detalles.push(lineaVacia()) }

async function buscarProductos (linea, valor, update, abort) {
  if (!valor || valor.length < 2) {
    if (linea.opciones.length) return update()
    return abort()
  }
  linea.buscando = true
  try {
    const { data } = await proxy.$axios.get('/bajas/productos', { params: { q: valor } })
    update(() => { linea.opciones = data })
  } catch {
    abort()
  } finally {
    linea.buscando = false
  }
}

async function onProductoElegido (linea) {
  linea.compra_detalle_id = null
  linea.lotes = []
  linea.disponible = 0
  linea.precio = 0
  linea.cantidad = null
  const producto = linea.opciones.find(p => p.id === linea.producto_id)
  linea.unidad = producto?.unidad?.abreviatura || producto?.unidad?.nombre || ''
  if (!linea.producto_id) return

  linea.cargandoLotes = true
  try {
    const { data } = await proxy.$axios.get(`/bajas/productos/${linea.producto_id}/lotes`)
    linea.lotes = data
    if (!data.length) proxy.$alert.error('El producto no tiene lotes con saldo disponible')
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los lotes')
  } finally {
    linea.cargandoLotes = false
  }
}

function onLoteElegido (linea) {
  const lote = linea.lotes.find(l => l.compra_detalle_id === linea.compra_detalle_id)
  linea.disponible = Number(lote?.disponible || 0)
  linea.precio = Number(lote?.precio || 0)
  if (Number(linea.cantidad || 0) > linea.disponible) linea.cantidad = linea.disponible
}

async function guardar () {
  if (!puedeGuardar.value) return
  saving.value = true
  try {
    await proxy.$axios.post('/bajas', {
      motivo: form.motivo,
      observacion: form.observacion || null,
      detalles: form.detalles.map(linea => ({
        producto_id: linea.producto_id,
        compra_detalle_id: linea.compra_detalle_id,
        cantidad: Number(linea.cantidad),
        motivo: linea.motivo || null,
      })),
    })
    proxy.$alert.success('Baja registrada y stock descontado')
    dialog.value = false
    recargar()
  } catch (error) {
    proxy.$alert.error(
      error.response?.data?.message ||
      Object.values(error.response?.data?.errors || {}).flat()[0] ||
      'No se pudo registrar la baja',
    )
  } finally {
    saving.value = false
  }
}

function anular (row) {
  proxy.$alert.dialog(`¿Anular la baja #${row.id} y devolver el stock a su lote?`)
    .onOk(async () => {
      try {
        await proxy.$axios.put(`/bajas/${row.id}/anular`, { motivo_anulacion: 'ANULADO DESDE EL LISTADO DE BAJAS' })
        proxy.$alert.success('Baja anulada, el stock volvió a su lote')
        recargar()
      } catch (error) {
        proxy.$alert.error(error.response?.data?.message || 'No se pudo anular la baja')
      }
    })
}

// ── Formato ───────────────────────────────────────────────────

function etiquetaMotivo (valor) {
  return motivos.value.find(m => m.value === valor)?.label || valor || '—'
}

function etiquetaLote (lote) {
  if (!lote) return ''
  return `Lote ${lote.lote || 'S/L'} · Vence ${fecha(lote.fecha_vencimiento)} · Disp. ${numero(lote.disponible)}`
}

function fecha (valor) {
  if (!valor) return '—'
  return new Intl.DateTimeFormat('es-BO').format(new Date(String(valor).slice(0, 10) + 'T00:00:00'))
}

function fechaHora (valor) {
  if (!valor) return '—'
  return new Date(valor).toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

function numero (valor) {
  return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 4 }).format(Number(valor || 0))
}

function dinero (valor) {
  return 'Bs ' + new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(valor || 0))
}

function textoDias (dias) {
  if (dias === null || dias === undefined) return 'Sin fecha'
  if (dias < 0) return `Vencido ${Math.abs(dias)}d`
  if (dias === 0) return 'Vence hoy'
  return `${dias}d`
}

function colorVencimiento (dias) {
  if (dias === null || dias === undefined) return 'grey'
  if (dias < 0) return 'negative'
  if (dias <= 30) return 'orange-9'
  return 'positive'
}

function requerido (valor) {
  return (valor !== null && valor !== '') || 'Campo requerido'
}
</script>

<style scoped>
.bajas-page :deep(.q-table th) {
  font-size: 11px;
  text-transform: uppercase;
  color: #616161;
}

.productos-cell {
  max-width: 460px;
  white-space: normal;
}
</style>
