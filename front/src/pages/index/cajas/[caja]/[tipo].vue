<template>
  <q-page class="q-pa-sm caja-page">
    <div v-if="proxy.$store.isLogged && !canVer" class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver {{ tituloCaja }}</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center q-mb-sm">
        <div>
          <div class="text-h6 text-weight-bold">{{ tituloTipo }} - {{ tituloCaja }}</div>
          <div class="text-caption text-grey-6">Control y comprobantes de movimientos</div>
        </div>
        <q-space />
        <q-chip square :color="esIngreso ? 'green-1' : 'red-1'" :text-color="esIngreso ? 'positive' : 'negative'" icon="account_balance_wallet">
          Total filtrado: <b class="q-ml-xs">{{ money(resumen.total) }} Bs</b>
        </q-chip>
        <q-btn v-if="canCrear" color="primary" icon="add" :label="'Registrar ' + tituloSingular.toLowerCase()" no-caps @click="nuevo" />
      </div>

      <div class="row q-col-gutter-xs q-mb-sm items-center">
        <div class="col-12 col-sm-auto">
          <q-input v-model="filtro.desde" dense outlined type="date" label="Desde" stack-label
                   :max="filtro.hasta" @change="buscar" />
        </div>
        <div class="col-12 col-sm-auto">
          <q-input v-model="filtro.hasta" dense outlined type="date" label="Hasta" stack-label
                   :min="filtro.desde" @change="buscar" />
        </div>
        <div class="col-auto"><q-input v-model="filtro.q" dense outlined clearable label="Buscar" debounce="350" style="width:230px" @update:model-value="buscar"><template #prepend><q-icon name="search" /></template></q-input></div>
        <q-btn flat round dense icon="refresh" color="primary" :loading="loading" @click="cargar"><q-tooltip>Actualizar</q-tooltip></q-btn>
        <q-btn-dropdown outline dense no-caps color="teal-8" icon="summarize" label="Reporte de ingresos y gastos" :disable="!rangoValido || exportando" class="q-ml-sm">
          <q-list dense>
            <q-item-label header class="q-py-xs">Ingresos y gastos · {{ etiquetaPeriodo }}</q-item-label>
            <q-item clickable v-close-popup :disable="exportando" @click="exportarReporte('pdf')">
              <q-item-section avatar><q-icon name="picture_as_pdf" color="red-8" /></q-item-section>
              <q-item-section>
                <q-item-label>Exportar PDF</q-item-label>
                <q-item-label caption>Ambas tablas, totales y saldo del periodo</q-item-label>
              </q-item-section>
            </q-item>
            <q-item clickable v-close-popup :disable="exportando" @click="exportarReporte('excel')">
              <q-item-section avatar><q-icon name="grid_on" color="green-8" /></q-item-section>
              <q-item-section>
                <q-item-label>Exportar Excel</q-item-label>
                <q-item-label caption>Con totales y saldo del periodo</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
        <q-space />
        <div class="text-caption text-grey-6">{{ resumen.cantidad || 0 }} movimientos</div>
      </div>

      <q-table ref="tableRef" :rows="rows" :columns="columns" row-key="id" dense flat bordered
               v-model:pagination="pagination" :rows-per-page-options="[15, 25, 50, 100]"
               :loading="loading" @request="onRequest">
        <template #body-cell-importe="props">
          <q-td :props="props" class="text-weight-bold" :class="esIngreso ? 'text-positive' : 'text-negative'">
            {{ money(props.row.importe) }} Bs
          </q-td>
        </template>
        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn flat round dense size="sm" icon="print" color="indigo" @click="imprimir(props.row)"><q-tooltip>Imprimir comprobante</q-tooltip></q-btn>
            <q-btn v-if="canEditar && props.row.estado !== 'ANULADO'" flat round dense size="sm" icon="edit" color="primary" @click="editar(props.row)" />
            <q-btn v-if="canAnular && props.row.estado !== 'ANULADO'" flat round dense size="sm" icon="block" color="negative" @click="abrirAnular(props.row)"><q-tooltip>Anular movimiento</q-tooltip></q-btn>
          </q-td>
        </template>
        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge :color="props.row.estado === 'ANULADO' ? 'negative' : 'positive'">{{ props.row.estado }}</q-badge>
            <q-tooltip v-if="props.row.estado === 'ANULADO'">{{ props.row.motivo_anulacion }}</q-tooltip>
          </q-td>
        </template>
      </q-table>

      <q-dialog v-model="dialog" persistent>
        <q-card style="width:min(96vw,620px);max-width:620px">
          <q-card-section class="row items-center bg-primary text-white q-py-sm">
            <q-icon :name="esIngreso ? 'add_card' : 'payments'" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-bold">{{ form.id ? 'Editar' : 'Registrar' }} {{ tituloSingular.toLowerCase() }}</span>
            <q-space /><q-btn flat round dense icon="close" @click="dialog = false" />
          </q-card-section>
          <q-card-section>
            <q-form @submit.prevent="guardar">
              <div class="row q-col-gutter-sm">
                <div class="col-12 col-sm-6"><q-input v-model="form.fecha_hora" dense outlined type="datetime-local" label="Fecha y hora" /></div>
                <div class="col-12 col-sm-6"><q-input v-model.number="form.importe" dense outlined type="number" step="0.01" min="0.01" label="Importe Bs *" input-class="text-right" :rules="[v => Number(v) > 0 || 'Ingrese un importe válido']" /></div>
                <div class="col-12 col-sm-6"><q-input v-model="form.categoria" dense outlined label="Categoría" v-uppercase /></div>
                <div class="col-12 col-sm-6"><q-input v-model="form.documento" dense outlined label="N.º documento / referencia" v-uppercase /></div>
                <div class="col-12"><q-input v-model="form.concepto" dense outlined label="Concepto *" v-uppercase :rules="[v => !!v || 'Requerido']" /></div>
                <div class="col-12"><q-input v-model="form.descripcion" dense outlined autogrow label="Descripción / detalle" v-uppercase /></div>
                <div class="col-12"><q-input v-model="form.beneficiario" dense outlined :label="esIngreso ? 'Recibido de / origen' : 'Pagado a / beneficiario'" v-uppercase /></div>
              </div>
              <div class="row justify-end q-gutter-sm q-mt-md">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialog = false" />
                <q-btn color="primary" label="Guardar" icon-right="save" type="submit" no-caps :loading="saving" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <q-dialog v-model="dialogAnular" persistent>
        <q-card style="width:min(94vw,440px)">
          <q-card-section class="row items-center bg-negative text-white q-py-sm">
            <q-icon name="block" class="q-mr-sm" /><span class="text-subtitle1 text-weight-bold">Anular movimiento</span>
          </q-card-section>
          <q-card-section>
            <div class="text-body2 q-mb-sm">El movimiento quedará visible y marcado como ANULADO. Esta acción no se puede revertir.</div>
            <q-input v-model="motivoAnulacion" dense outlined autogrow autofocus label="Motivo de anulación *" v-uppercase />
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat label="Cancelar" no-caps @click="dialogAnular = false" />
              <q-btn color="negative" icon="block" label="Anular" no-caps :loading="anulando" @click="anular" />
            </div>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { formatBoliviaDateTime } from '../../../../addons/dateTime'
import { imprimirCajaMovimiento } from '../../../../addons/cajaMovimientoPrint'

const { proxy } = getCurrentInstance()
const caja = computed(() => String(proxy.$route.params.caja || '').toUpperCase() === 'GENERAL' ? 'GENERAL' : 'ADMINISTRATIVA')
const tipo = computed(() => String(proxy.$route.params.tipo || '').toUpperCase() === 'INGRESOS' ? 'INGRESO' : 'GASTO')
const esIngreso = computed(() => tipo.value === 'INGRESO')
const tituloCaja = computed(() => caja.value === 'GENERAL' ? 'Caja General' : 'Caja Administrativa')
const tituloTipo = computed(() => esIngreso.value ? 'Ingresos' : 'Gastos')
const tituloSingular = computed(() => esIngreso.value ? 'Ingreso' : 'Gasto')

const canVer = computed(() => proxy.$store.hasPermission('Ver ' + tituloCaja.value))
const canCrear = computed(() => proxy.$store.hasPermission('Crear ' + tituloCaja.value))
const canEditar = computed(() => proxy.$store.hasPermission('Editar ' + tituloCaja.value))
const canAnular = computed(() => proxy.$store.hasPermission('Anular ' + tituloCaja.value))

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const dialogAnular = ref(false)
const movimientoAnular = ref(null)
const motivoAnulacion = ref('')
const anulando = ref(false)
const form = ref({})
const resumen = ref({ cantidad: 0, total: 0 })
const exportando = ref(false)

function aISO (fecha) {
  const d = new Date(fecha)
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().slice(0, 10)
}
const hoyISO = aISO(new Date())
const filtro = ref({ q: '', desde: hoyISO, hasta: hoyISO })
const tableRef = ref(null)
const pagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })
const rangoValido = computed(() => !!filtro.value.desde && !!filtro.value.hasta && filtro.value.desde <= filtro.value.hasta)
const etiquetaPeriodo = computed(() => filtro.value.desde + ' al ' + filtro.value.hasta)

function validarRango () {
  if (rangoValido.value) return true
  proxy.$alert.error('Seleccione ambas fechas. Desde debe ser anterior o igual a Hasta.')
  return false
}

const columns = computed(() => [
  { name: 'actions', label: 'Opciones', align: 'center' },
  { name: 'fecha_hora', label: 'Fecha y hora', field: row => formatBoliviaDateTime(row.fecha_hora), align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'categoria', label: 'Categoría', field: row => row.categoria || '—', align: 'left' },
  { name: 'concepto', label: 'Concepto', field: 'concepto', align: 'left' },
  { name: 'beneficiario', label: esIngreso.value ? 'Origen' : 'Beneficiario', field: row => row.beneficiario || '—', align: 'left' },
  { name: 'documento', label: 'Documento', field: row => row.documento || '—', align: 'left' },
  { name: 'user', label: 'Registrado por', field: row => row.user?.name || '—', align: 'left' },
  { name: 'importe', label: 'Importe', field: 'importe', align: 'right' },
])

function money (value) { return Number(value || 0).toFixed(2) }
function fechaLocal () {
  const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().slice(0, 16)
}
function nuevo () {
  form.value = { fecha_hora: fechaLocal(), categoria: '', concepto: '', descripcion: '', beneficiario: '', documento: '', importe: null }
  dialog.value = true
}
function editar (row) {
  form.value = { ...row, fecha_hora: row.fecha_hora ? String(row.fecha_hora).replace(' ', 'T').slice(0, 16) : fechaLocal() }
  dialog.value = true
}
function buscar () {
  if (!validarRango()) return
  pagination.value.page = 1
  if (tableRef.value) tableRef.value.requestServerInteraction()
  else cargar()
}
function onRequest ({ pagination: pag }) {
  pagination.value.page = pag.page; pagination.value.rowsPerPage = pag.rowsPerPage; cargar()
}
async function cargar () {
  if (!canVer.value || !validarRango()) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('caja-movimientos', { params: {
      caja: caja.value, tipo: tipo.value, q: filtro.value.q || undefined,
      desde: filtro.value.desde || undefined, hasta: filtro.value.hasta || undefined,
      page: pagination.value.page, per_page: pagination.value.rowsPerPage,
    } })
    rows.value = data.movimientos.data || []
    pagination.value.rowsNumber = data.movimientos.total || 0
    resumen.value = data.resumen || { cantidad: 0, total: 0 }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los movimientos')
  } finally { loading.value = false }
}
async function guardar () {
  saving.value = true
  try {
    if (form.value.id) await proxy.$axios.put('caja-movimientos/' + form.value.id, form.value)
    else await proxy.$axios.post('caja-movimientos', { ...form.value, caja: caja.value, tipo: tipo.value })
    proxy.$alert.success(tituloSingular.value + ' guardado correctamente')
    dialog.value = false; cargar()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo guardar')
  } finally { saving.value = false }
}
function abrirAnular (row) {
  movimientoAnular.value = row
  motivoAnulacion.value = ''
  dialogAnular.value = true
}
async function anular () {
  if (!motivoAnulacion.value.trim()) { proxy.$alert.error('Indique el motivo de anulación'); return }
  anulando.value = true
  try {
    await proxy.$axios.put('caja-movimientos/' + movimientoAnular.value.id + '/anular', { motivo_anulacion: motivoAnulacion.value })
    proxy.$alert.success('Movimiento anulado')
    dialogAnular.value = false
    cargar()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo anular')
  } finally { anulando.value = false }
}
function imprimir (row) {
  imprimirCajaMovimiento(row, tituloCaja.value)
}

/**
 * Reporte por fechas de la caja: incluye ingresos y gastos, no solo el tipo
 * de la pantalla actual. El PDF se abre en una pestaña, el Excel se descarga.
 */
async function exportarReporte (formato) {
  if (!validarRango()) return
  exportando.value = true
  try {
    const { data } = await proxy.$axios.get('caja-movimientos/reporte/' + formato, {
      params: { caja: caja.value, desde: filtro.value.desde, hasta: filtro.value.hasta },
      responseType: 'blob',
    })
    const tipoMime = formato === 'pdf'
      ? 'application/pdf'
      : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    const url = window.URL.createObjectURL(new Blob([data], { type: tipoMime }))

    if (formato === 'pdf') {
      window.open(url, '_blank')
    } else {
      const a = document.createElement('a')
      a.href = url
      a.download = `caja_${caja.value.toLowerCase()}_${filtro.value.desde}_a_${filtro.value.hasta}.xlsx`
      a.click()
    }
    setTimeout(() => window.URL.revokeObjectURL(url), 30000)
  } catch (error) {
    proxy.$alert.error('No se pudo generar el reporte')
  } finally {
    exportando.value = false
  }
}

watch([() => proxy.$store.isLogged, caja, tipo], ([logged]) => {
  if (logged && canVer.value) { pagination.value.page = 1; cargar() }
}, { immediate: true })
</script>

<style scoped>
.caja-page :deep(.q-table th), .caja-page :deep(.q-table td) { font-size:11px; padding:3px 7px }
.caja-page :deep(.q-field--dense .q-field__control), .caja-page :deep(.q-field--dense .q-field__marginal) { min-height:34px; height:34px }
</style>
