<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-gutter-sm q-mb-md">
      <div>
        <div class="text-h6">Reportes generales de laboratorio</div>
        <div class="text-caption text-grey-7">Una fila por prueba · Período según fecha de solicitud · Todos los estados</div>
      </div>
      <q-space />
      <q-btn outline color="positive" icon="grid_on" label="Excel" no-caps :loading="exportando === 'excel'" :disable="loading || !!exportando" @click="exportar('excel')" />
      <q-btn outline color="negative" icon="picture_as_pdf" label="PDF" no-caps :loading="exportando === 'pdf'" :disable="loading || !!exportando" @click="exportar('pdf')" />
    </div>

    <q-card flat bordered class="q-pa-md q-mb-md">
      <q-form @submit="aplicar">
        <div class="row q-col-gutter-sm items-start">
          <div class="col-12 col-sm-3">
            <q-input v-model="mes" outlined dense type="month" label="Mes" @update:model-value="cambiarMes" />
          </div>
          <div class="col-12 col-sm-3">
            <q-input v-model="filters.desde" outlined dense type="date" label="Desde" :rules="[v => !!v || 'Seleccione una fecha']" @update:model-value="mes = ''" />
          </div>
          <div class="col-12 col-sm-3">
            <q-input v-model="filters.hasta" outlined dense type="date" label="Hasta" :rules="[v => !!v || 'Seleccione una fecha', v => v >= filters.desde || 'Debe ser igual o posterior a Desde']" @update:model-value="mes = ''" />
          </div>
          <div class="col-12 col-sm-3">
            <q-input v-model="filters.nombre" v-uppercase outlined dense clearable label="Nombre del laboratorio / prueba" maxlength="255">
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </div>
        </div>
        <div class="row q-gutter-sm">
          <q-btn flat color="primary" label="Mes pasado" no-caps @click="seleccionarMes(-1)" />
          <q-btn flat color="primary" label="Este mes" no-caps @click="seleccionarMes(0)" />
          <q-space />
          <q-btn type="submit" color="primary" icon="filter_alt" label="Aplicar filtros" no-caps :loading="loading" />
        </div>
      </q-form>
    </q-card>

    <div class="row q-col-gutter-sm q-mb-md">
      <div v-for="stat in indicadores" :key="stat.label" class="col-12 col-sm-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">{{ stat.label }}</div>
          <div class="text-h5 text-primary">{{ stat.value }}</div>
        </q-card>
      </div>
    </div>
    <div class="text-caption text-grey-7 q-mb-sm">
      Período aplicado: {{ aplicado.desde }} al {{ aplicado.hasta }} · {{ aplicado.nombre || 'Todos los laboratorios' }}.
      Las exportaciones incluyen todas las páginas de este filtro.
    </div>
    <q-table ref="tableRef" flat bordered :rows="rows" :columns="columns" row-key="id" :loading="loading"
             v-model:pagination="pagination" :rows-per-page-options="[15, 25, 50, 100]" @request="onRequest"
             no-data-label="No hay laboratorios para los filtros seleccionados." />
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'

const { proxy } = getCurrentInstance()
const tableRef = ref(null)
const rows = ref([])
const loading = ref(false)
const exportando = ref(null)
const mes = ref('')
const filters = ref({ desde: '', hasta: '', nombre: '' })
const aplicado = ref({})
const resumen = ref({ solicitudes: 0, pruebas: 0, importe: 0 })
const pagination = ref({ page: 1, rowsPerPage: 25, rowsNumber: 0 })
const money = value => Number(value || 0).toFixed(2)
const indicadores = computed(() => [
  { label: 'Solicitudes', value: resumen.value.solicitudes },
  { label: 'Pruebas de laboratorio', value: resumen.value.pruebas },
  { label: 'Importe de pruebas (Bs)', value: money(resumen.value.importe) },
])
const columns = [
  { name: 'fecha', label: 'Fecha de solicitud', field: row => row.solicitude?.fecha_solicitud, align: 'left' },
  { name: 'codigo', label: 'Solicitud', field: row => row.solicitude?.codigo_solicitud, align: 'left' },
  { name: 'paciente', label: 'Paciente', field: row => row.solicitude?.paciente?.nombre_completo || '—', align: 'left' },
  { name: 'ci', label: 'CI', field: row => row.solicitude?.paciente?.ci || '—', align: 'left' },
  { name: 'laboratorio', label: 'Laboratorio / prueba', field: 'producto_nombre', align: 'left' },
  { name: 'estado', label: 'Estado', field: row => row.solicitude?.estado, align: 'left' },
  { name: 'precio', label: 'Importe (Bs)', field: 'precio', format: money, align: 'right' },
]
function cambiarMes () {
  if (!/^\d{4}-\d{2}$/.test(mes.value)) return
  const [year, month] = mes.value.split('-').map(Number)
  filters.value.desde = `${mes.value}-01`
  filters.value.hasta = `${mes.value}-${new Date(year, month, 0).getDate()}`
  aplicar()
}
function seleccionarMes (offset) {
  const date = new Date()
  date.setDate(1)
  date.setMonth(date.getMonth() + offset)
  mes.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
  cambiarMes()
}
function aplicar () {
  if (!filters.value.desde || !filters.value.hasta || filters.value.hasta < filters.value.desde) {
    proxy.$alert.error('Seleccione un rango de fechas válido')
    return
  }
  aplicado.value = { ...filters.value }
  pagination.value.page = 1
  tableRef.value?.requestServerInteraction()
}
let requestId = 0
async function onRequest ({ pagination: page }) {
  const id = ++requestId
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('reportes-laboratorio', {
      params: { ...aplicado.value, page: page.page, per_page: page.rowsPerPage },
    })
    if (id !== requestId) return
    rows.value = data.data
    resumen.value = data.resumen
    pagination.value = { ...page, page: data.current_page, rowsPerPage: data.per_page, rowsNumber: data.total }
  } catch (error) {
    if (id !== requestId) return
    rows.value = []
    resumen.value = { solicitudes: 0, pruebas: 0, importe: 0 }
    pagination.value.rowsNumber = 0
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar el reporte')
  } finally {
    if (id === requestId) loading.value = false
  }
}
async function exportar (tipo) {
  exportando.value = tipo
  const params = { ...aplicado.value }
  try {
    const { data } = await proxy.$axios.get(`reportes-laboratorio/export-${tipo}`, { params, responseType: 'blob' })
    const url = URL.createObjectURL(data)
    const link = document.createElement('a')
    link.href = url
    link.download = `laboratorios_${params.desde}_${params.hasta}.${tipo === 'excel' ? 'xlsx' : 'pdf'}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1000)
  } catch {
    proxy.$alert.error('No se pudo exportar el reporte')
  } finally {
    exportando.value = null
  }
}
onMounted(() => seleccionarMes(-1))
</script>
