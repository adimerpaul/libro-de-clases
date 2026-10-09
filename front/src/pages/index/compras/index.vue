<template>
  <q-page class="q-pa-md compras-compactas">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver compras</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="row items-center q-mb-md">
        <div>
          <div class="text-h5 text-weight-bold">Compras</div>
          <div class="text-body2 text-grey-6">Historial de compras a proveedores</div>
        </div>
        <q-space />
        <q-btn rounded outline color="grey-7" icon="local_shipping" label="Proveedores" no-caps
               class="q-mr-sm" to="/proveedores">
          <q-tooltip>Administrar proveedores</q-tooltip>
        </q-btn>
        <q-btn v-if="canCrear" rounded unelevated color="primary" icon="add_shopping_cart"
               label="Nueva compra" no-caps to="/compras/crear" />
      </div>

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-sm-4">
          <q-card flat class="bg-primary text-white q-pa-md rounded-borders full-height">
            <div class="text-caption text-teal-2 text-uppercase text-weight-bold">Compras activas</div>
            <div class="text-h5 text-weight-bold">{{ money(resumen.total_compras) }} <span class="text-caption text-teal-2">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-4">
          <q-card flat bordered class="q-pa-md rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Anuladas</div>
            <div class="text-h5 text-weight-bold text-negative">{{ money(resumen.total_anuladas) }} <span class="text-caption text-grey-6">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-4">
          <q-card flat bordered class="q-pa-md rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Total registros</div>
            <div class="text-h5 text-weight-bold">{{ resumen.cantidad }}</div>
          </q-card>
        </div>
      </div>

      <div class="row items-center q-col-gutter-xs q-mb-xs">
        <div class="col-auto">
          <q-btn dense outline no-caps color="primary" icon="date_range" label="Esta semana"
                 @click="filtrarSemanaActual" />
        </div>
        <div class="col-auto">
          <q-input v-model="filtro.fecha_inicio" label="Fecha inicio" dense outlined type="date"
                   style="width:150px" @update:model-value="onFiltroChange" />
        </div>
        <div class="col-auto">
          <q-input v-model="filtro.fecha_fin" label="Fecha fin" dense outlined type="date"
                   style="width:150px" @update:model-value="onFiltroChange" />
        </div>
        <div class="col-auto">
          <q-select v-model="filtro.proveedor_id" label="Proveedor" dense outlined clearable
                    :options="allProveedores" option-value="id" option-label="nombre"
                    emit-value map-options style="width:170px" @update:model-value="onFiltroChange" />
        </div>
        <div class="col-auto">
          <q-select v-model="filtro.estado" label="Estado" dense outlined clearable
                    :options="['ACTIVO', 'ANULADO']" style="width:130px" @update:model-value="onFiltroChange" />
        </div>
        <q-space />
        <q-btn outline rounded no-caps color="grey-7" icon="table_view" label="Excel"
               :loading="exportingExcel" @click="exportExcel">
          <q-tooltip>Exportar Excel</q-tooltip>
        </q-btn>
      </div>

      <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-compacta">
        <thead>
          <tr class="bg-grey-1 text-grey-7 text-uppercase">
            <th class="text-left" style="width:64px"></th>
            <th class="text-left">ID</th>
            <th class="text-left">Fecha</th>
            <th class="text-left">Proveedor</th>
            <th class="text-left">Usuario</th>
            <th class="text-center">Estado</th>
            <th class="text-left">Pago</th>
            <th class="text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loadingCompras">
            <td colspan="8" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
          </tr>
          <tr v-else-if="!compras.length">
            <td colspan="8" class="text-center text-grey-5 q-pa-md">Sin datos</td>
          </tr>
          <tr v-else v-for="row in compras" :key="row.id">
            <td class="q-pa-xs">
              <q-btn-dropdown label="Opciones" no-caps size="10px" dense rounded unelevated color="primary">
                <q-list dense>
                  <q-item clickable v-close-popup @click="verDetalle(row)">
                    <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                    <q-item-section><q-item-label>Ver detalle ({{ row.detalles_count }})</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="imprimir(row)">
                    <q-item-section avatar><q-icon name="print" color="primary" /></q-item-section>
                    <q-item-section><q-item-label>Imprimir</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="exportCompraExcel(row)">
                    <q-item-section avatar>
                      <q-icon v-if="exportingCompraId !== row.id" name="table_view" color="green-8" />
                      <q-spinner v-else color="green-8" size="20px" />
                    </q-item-section>
                    <q-item-section><q-item-label>Exportar Excel con fórmulas</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" :disable="row.estado === 'ANULADO'" clickable v-close-popup @click="anular(row)">
                    <q-item-section avatar><q-icon name="block" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Anular</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td>{{ row.id }}</td>
            <td>{{ formatFecha(row.fecha_hora) }}</td>
            <td>{{ row.proveedor ? row.proveedor.nombre : '—' }}</td>
            <td>{{ row.user ? row.user.name : '—' }}</td>
            <td class="text-center">
              <q-badge rounded
                       :color="row.estado === 'ANULADO' ? 'red-1' : 'green-1'"
                       :text-color="row.estado === 'ANULADO' ? 'negative' : 'positive'"
                       class="text-weight-bold">{{ row.estado }}</q-badge>
            </td>
            <td>{{ row.tipo_pago }}</td>
            <td class="text-right">{{ money(row.total) }}</td>
          </tr>
        </tbody>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-xs q-px-xs">
        <div class="text-caption text-grey-6">
          Total: {{ totalCompras }} | Página {{ pageCompras }} de {{ pagesCompras }}
        </div>
        <q-pagination v-model="pageCompras" :max="pagesCompras" :max-pages="6"
                      boundary-links direction-links size="sm" @update:model-value="loadCompras" />
      </div>

    </template>

    <!-- DIALOG DETALLE COMPRA -->
    <q-dialog v-model="dialogDetalle">
      <q-card style="width:min(96vw,700px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Detalle de compra #{{ detalleCompra?.id }}</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogDetalle = false" />
        </q-card-section>
        <q-card-section style="max-height:70vh;overflow-y:auto">
          <q-markup-table dense flat bordered separator="horizontal">
            <thead>
              <tr class="bg-grey-1 text-grey-7 text-uppercase">
                <th class="text-left">Producto</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
                <th class="text-left">Lote</th>
                <th class="text-left">Vencimiento</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in detalleCompra?.detalles || []" :key="d.id">
                <td>{{ d.nombre }}</td>
                <td class="text-right">{{ d.cantidad }}</td>
                <td class="text-right">{{ money(d.precio) }}</td>
                <td class="text-right">{{ money(d.total) }}</td>
                <td>{{ d.lote || '—' }}</td>
                <td>{{ d.fecha_vencimiento ? d.fecha_vencimiento.slice(0, 10) : '—' }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { imprimirCompra } from '../../../addons/compraPrint'
import { formatBoliviaDateTime } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Compras'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Compras'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Compras'))

const resumen        = ref({ total_compras: 0, total_anuladas: 0, cantidad: 0 })
const allProveedores = ref([])

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return formatBoliviaDateTime(v) }

// ── Historial ──────────────────────────────────────────────────
const compras        = ref([])
const loadingCompras = ref(false)
const pageCompras    = ref(1)
const totalCompras   = ref(0)
const perCompras     = 15
const exportingExcel = ref(false)
function fechaIsoUtc (date) {
  return date.toISOString().slice(0, 10)
}

function rangoSemanaActual () {
  const partes = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/La_Paz',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(new Date())
  const fecha = Object.fromEntries(partes.filter(p => p.type !== 'literal').map(p => [p.type, Number(p.value)]))
  const hoy = new Date(Date.UTC(fecha.year, fecha.month - 1, fecha.day))
  const diasDesdeLunes = (hoy.getUTCDay() + 6) % 7
  const inicio = new Date(hoy)
  inicio.setUTCDate(hoy.getUTCDate() - diasDesdeLunes)
  const fin = new Date(inicio)
  fin.setUTCDate(inicio.getUTCDate() + 6)

  return { fecha_inicio: fechaIsoUtc(inicio), fecha_fin: fechaIsoUtc(fin) }
}

const semanaActual = rangoSemanaActual()
const filtro = ref({ ...semanaActual, proveedor_id: null, estado: null })

const pagesCompras = computed(() => Math.max(1, Math.ceil(totalCompras.value / perCompras)))

let timerFiltro = null
function onFiltroChange () {
  clearTimeout(timerFiltro)
  timerFiltro = setTimeout(() => { pageCompras.value = 1; loadCompras() }, 350)
}

function filtrarSemanaActual () {
  const semana = rangoSemanaActual()
  filtro.value.fecha_inicio = semana.fecha_inicio
  filtro.value.fecha_fin = semana.fecha_fin
  pageCompras.value = 1
  loadCompras()
}

async function loadCompras () {
  loadingCompras.value = true
  try {
    const res = await proxy.$axios.get('compras', {
      params: {
        page: pageCompras.value,
        per_page: perCompras,
        fecha_inicio: filtro.value.fecha_inicio,
        fecha_fin: filtro.value.fecha_fin,
        proveedor_id: filtro.value.proveedor_id,
        estado: filtro.value.estado,
      },
    })
    const data = res.data || {}
    resumen.value = data.resumen || { total_compras: 0, total_anuladas: 0, cantidad: 0 }
    compras.value = data.compras?.data || []
    totalCompras.value = data.compras?.total || 0
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar compras')
  } finally {
    loadingCompras.value = false
  }
}

// Proveedores para el filtro del historial.
async function loadAllProveedores () {
  try {
    const res = await proxy.$axios.get('proveedores')
    allProveedores.value = res.data || []
  } catch (e) { /* silent */ }
}

const dialogDetalle = ref(false)
const detalleCompra = ref(null)
async function verDetalle (row) {
  try {
    const res = await proxy.$axios.get('compras/' + row.id)
    detalleCompra.value = res.data
    dialogDetalle.value = true
  } catch (e) {
    proxy.$alert.error('Error al cargar el detalle')
  }
}

async function imprimir (row) {
  try {
    const res = await proxy.$axios.get('compras/' + row.id)
    imprimirCompra(res.data)
  } catch (e) {
    proxy.$alert.error('Error al imprimir la compra')
  }
}

const exportingCompraId = ref(null)
async function exportCompraExcel (row) {
  exportingCompraId.value = row.id
  try {
    const res = await proxy.$axios.get('compras/' + row.id + '/export-excel', {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = 'compra_' + row.id + '_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al generar el Excel de la compra')
  } finally {
    exportingCompraId.value = null
  }
}

function anular (row) {
  proxy.$alert.dialog('¿Desea anular la compra #' + row.id + '? Esto revertirá el stock generado.').onOk(() => {
    proxy.$axios.delete('compras/' + row.id)
      .then(() => { proxy.$alert.success('Compra anulada'); loadCompras() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error al anular'))
  })
}

async function exportExcel () {
  exportingExcel.value = true
  try {
    const res = await proxy.$axios.get('compras/export-excel', {
      params: { ...filtro.value },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = 'compras_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    proxy.$alert.error('Error al generar Excel')
  } finally {
    exportingExcel.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadCompras()
  loadAllProveedores()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>

<style scoped>
.compras-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.compras-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}

.compras-compactas :deep(.q-field--dense .q-field__native),
.compras-compactas :deep(.q-field--dense .q-field__input),
.compras-compactas :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}

.compras-compactas :deep(.q-field--dense .q-field__append),
.compras-compactas :deep(.q-field--dense .q-field__prepend) {
  height: 30px;
}

.compras-compactas :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}
</style>
