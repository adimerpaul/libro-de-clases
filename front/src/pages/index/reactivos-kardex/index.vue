<template>
  <q-page class="q-pa-sm">
    <div class="row items-center q-mb-md">
      <div><div class="text-h6 text-weight-bold">Kardex de reactivos</div><div class="text-caption text-grey-7">Consumo por periodo calculado a partir de pruebas de laboratorio</div></div>
    </div>
    <q-form class="row q-col-gutter-sm items-start q-mb-md" @submit="consultar">
      <div class="col-12 col-sm-5">
        <q-select v-model="reactivo" :options="opciones" option-label="nombre" label="Reactivo" outlined dense
                  use-input clearable input-debounce="300" @filter="filtrarReactivos" :rules="[v => !!v || 'Seleccione un reactivo']" />
      </div>
      <div class="col-6 col-sm-2">
        <q-input v-model="fechaInicio" type="date" outlined dense stack-label label="Desde"
                 :rules="[v => !!v || 'Requerido']" @update:model-value="rango = null" />
      </div>
      <div class="col-6 col-sm-2">
        <q-input v-model="fechaFin" type="date" outlined dense stack-label label="Hasta"
                 :rules="[v => !!v || 'Requerido', v => !fechaInicio || v >= fechaInicio || 'Debe ser igual o posterior a Desde']"
                 @update:model-value="rango = null" />
      </div>
      <div class="col-auto"><q-btn label="Consultar" icon="search" type="submit" color="primary" no-caps :loading="loading" /></div>
      <div class="col-auto"><q-btn label="Imprimir PDF" icon="print" color="teal" no-caps :disable="!datos || cambiado" :loading="printing" @click="imprimir" /></div>
    </q-form>
    <!-- Rangos rápidos: por defecto el mes actual -->
    <div class="q-mb-sm">
      <q-btn-toggle v-model="rango" dense unelevated no-caps toggle-color="primary"
                    color="grey-3" text-color="grey-8" :options="rangos"
                    @update:model-value="aplicarRango" />
    </div>
    <q-banner v-if="cambiado && datos" dense class="bg-amber-1 q-mb-sm">Pulsa Consultar para actualizar el reporte con los filtros seleccionados.</q-banner>
    <template v-if="datos">
      <q-banner dense class="bg-blue-1 text-blue-10 q-mb-sm">{{ datos.nota }}</q-banner>
      <div class="row items-center q-gutter-sm q-mb-sm">
        <b>{{ datos.reactivo.nombre }} · {{ datos.periodo }}</b>
        <q-chip>{{ datos.cantidad_pruebas }} pruebas</q-chip>
        <q-chip color="teal-1" text-color="teal-10">Salida calculada: {{ numero(datos.total_salidas) }} {{ datos.reactivo.unidad }}</q-chip>
      </div>
      <q-table flat bordered dense :rows="datos.movimientos" :columns="columns" row-key="clave" :pagination="{ rowsPerPage: 31 }" :rows-per-page-options="[31, 50, 0]" no-data-label="Sin pruebas realizadas para este reactivo en el periodo seleccionado">
        <template #body-cell-observaciones="props"><q-td :props="props" style="white-space:normal;min-width:200px;max-width:400px">{{ props.value }}</q-td></template>
      </q-table>
    </template>
    <q-banner v-else class="bg-grey-2">Selecciona un reactivo y un rango de fechas para consultar su kardex. Por defecto se muestra el mes actual.</q-banner>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()

// ── Rango de fechas: por defecto el mes actual ──
function ymd (fecha) {
  return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`
}
const rangos = [
  { label: 'Este mes', value: 'mes' },
  { label: 'Mes pasado', value: 'mes_pasado' },
  { label: 'Hoy', value: 'hoy' },
  { label: 'Este año', value: 'anio' },
]
const rango = ref('mes')
const fechaInicio = ref('')
const fechaFin = ref('')

function aplicarRango (valor) {
  const hoy = new Date()
  if (valor === 'hoy') {
    fechaInicio.value = ymd(hoy)
    fechaFin.value = ymd(hoy)
  } else if (valor === 'mes_pasado') {
    fechaInicio.value = ymd(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1))
    fechaFin.value = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 0))
  } else if (valor === 'anio') {
    fechaInicio.value = ymd(new Date(hoy.getFullYear(), 0, 1))
    fechaFin.value = ymd(new Date(hoy.getFullYear(), 11, 31))
  } else if (valor === 'mes') {
    fechaInicio.value = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 1))
    fechaFin.value = ymd(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0))
  }
  // Con un reactivo ya elegido, el rango rápido consulta de inmediato.
  if (valor && reactivo.value) consultar()
}

const reactivo = ref(null)
const opciones = ref([])
const datos = ref(null)
const loading = ref(false)
const printing = ref(false)
const cambiado = computed(() => datos.value && (
  datos.value.fecha_inicio !== fechaInicio.value ||
  datos.value.fecha_fin !== fechaFin.value ||
  datos.value.reactivo.id !== reactivo.value?.id))

aplicarRango('mes')
const numero = value => value == null ? 'Sin registro' : Number(value).toLocaleString('es-BO', { maximumFractionDigits: 4 })
const columns = [
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left' },
  { name: 'ingreso', label: 'Ingreso', field: row => numero(row.ingreso), align: 'right' },
  { name: 'marca', label: 'Marca', field: row => row.marca || '-', align: 'left' },
  { name: 'lote', label: 'Lote', field: row => row.lote || '-', align: 'left' },
  { name: 'vencimiento', label: 'Vencimiento', field: row => row.vencimiento || '-', align: 'left' },
  { name: 'salida', label: 'Salida calculada', field: row => numero(row.salida), align: 'right' },
  { name: 'saldo', label: 'Saldo', field: row => numero(row.saldo), align: 'right' },
  { name: 'responsable', label: 'Registrado por', field: row => row.responsable || 'Sin registro', align: 'left' },
  { name: 'observaciones', label: 'Pruebas realizadas', field: 'observaciones', align: 'left' },
]

async function filtrarReactivos (q, update, abort) {
  try {
    const { data } = await proxy.$axios.get('reactivos', { params: { q, per_page: 100 } })
    update(() => { opciones.value = data.data })
  } catch (error) {
    abort()
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los reactivos')
  }
}

async function consultar () {
  if (!reactivo.value || !fechaInicio.value || !fechaFin.value || fechaFin.value < fechaInicio.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('reactivos-kardex', {
      params: { reactivo_id: reactivo.value.id, fecha_inicio: fechaInicio.value, fecha_fin: fechaFin.value },
    })
    data.movimientos = data.movimientos.map((fila, index) => ({ ...fila, clave: index }))
    datos.value = data
  } catch (error) {
    datos.value = null
    proxy.$alert.error(error.response?.data?.message || 'No se pudo consultar el kardex')
  } finally { loading.value = false }
}

async function imprimir () {
  if (!datos.value || cambiado.value) return
  printing.value = true
  try {
    const { data } = await proxy.$axios.get('reactivos-kardex/pdf', {
      params: { reactivo_id: datos.value.reactivo.id, fecha_inicio: datos.value.fecha_inicio, fecha_fin: datos.value.fecha_fin },
      responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = `kardex_reactivo_${datos.value.reactivo.id}_${datos.value.fecha_inicio}_${datos.value.fecha_fin}.pdf`
    enlace.click()
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (error) {
    proxy.$alert.error('No se pudo generar el PDF')
  } finally { printing.value = false }
}
</script>
