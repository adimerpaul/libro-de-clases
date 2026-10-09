<template>
  <q-page class="q-pa-sm">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Compras por paciente</div>
        <div class="text-caption text-grey-6">Busque un paciente y vea todo lo que compró y su total acumulado</div>
      </div>
    </div>

    <!-- Buscador de paciente -->
    <div class="row q-col-gutter-sm items-center q-mb-sm">
      <div class="col-12 col-sm-6">
        <q-select v-model="paciente" dense outlined clearable use-input hide-bottom-space
                  input-debounce="350" :options="opcionesPaciente" option-label="nombre_completo"
                  label="Paciente (nombre o CI)" @filter="buscarPacientes" @update:model-value="seleccionar">
          <template v-slot:prepend><q-icon name="person_search" /></template>
          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section>
                <q-item-label>{{ scope.opt.nombre_completo }}</q-item-label>
                <q-item-label caption>
                  <template v-if="scope.opt.ci">CI {{ scope.opt.ci }} · </template>
                  {{ scope.opt.compras_count }} {{ scope.opt.compras_count === 1 ? 'compra' : 'compras' }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <template v-slot:no-option>
            <q-item><q-item-section class="text-grey">Escriba al menos 2 letras</q-item-section></q-item>
          </template>
        </q-select>
      </div>
      <template v-if="paciente">
        <div class="col-6 col-sm-2">
          <q-input v-model="fechaInicio" type="date" label="Desde" dense outlined stack-label clearable
                   hide-bottom-space @update:model-value="buscar" />
        </div>
        <div class="col-6 col-sm-2">
          <q-input v-model="fechaFin" type="date" label="Hasta" dense outlined stack-label clearable
                   hide-bottom-space @update:model-value="buscar" />
        </div>
      </template>
    </div>

    <div v-if="!paciente" class="column items-center q-pa-xl text-grey-6">
      <q-icon name="person_search" size="56px" color="grey-4" />
      <div class="q-mt-sm">Seleccione un paciente para ver sus compras</div>
    </div>

    <template v-else>
      <!-- Datos del paciente y resumen de compras -->
      <q-card v-if="info" flat bordered class="q-mb-sm">
        <q-card-section class="q-py-sm">
          <div class="text-subtitle1 text-weight-bold">{{ info.paciente.nombre_completo }}</div>
          <div class="text-caption text-grey-7">
            <span v-if="info.paciente.ci">CI {{ info.paciente.ci }} · </span>
            <span v-if="info.paciente.edad !== null && info.paciente.edad !== undefined">{{ info.paciente.edad }} años · </span>
            <span v-if="info.paciente.telefono">Tel. {{ info.paciente.telefono }} · </span>
            {{ info.paciente.seguro || 'PARTICULAR' }}
          </div>
        </q-card-section>
        <q-separator />
        <q-card-section class="q-py-sm">
          <div class="row q-col-gutter-sm">
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Total comprado</div>
              <div class="text-h6 text-weight-bold text-primary">{{ money(info.resumen.total) }} Bs</div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">N.º de compras</div>
              <div class="text-h6 text-weight-bold">{{ info.resumen.compras }}</div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Pendiente de cobro</div>
              <div class="text-h6 text-weight-bold" :class="info.resumen.pendiente > 0 ? 'text-orange-9' : ''">
                {{ money(info.resumen.pendiente) }} Bs
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Última compra</div>
              <div class="text-body2 text-weight-medium q-mt-xs">{{ formatBoliviaDateTime(info.resumen.ultima) }}</div>
              <div v-if="info.resumen.primera" class="text-caption text-grey-6">
                Primera: {{ formatBoliviaDate(info.resumen.primera) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Historial paginado -->
      <q-markup-table dense flat bordered wrap-cells>
        <thead class="bg-grey-2">
          <tr>
            <th class="text-left" style="width:70px">N.º</th>
            <th class="text-left" style="width:130px">Fecha</th>
            <th class="text-left">Detalle</th>
            <th class="text-left" style="width:120px">Seguro</th>
            <th class="text-left" style="width:100px">Pago</th>
            <th class="text-left" style="width:120px">Registrado por</th>
            <th class="text-center" style="width:90px">Estado</th>
            <th class="text-right" style="width:90px">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="8" class="text-center"><q-spinner color="primary" /></td></tr>
          <tr v-else-if="!ventas.length"><td colspan="8" class="text-center text-grey-6">Sin compras registradas</td></tr>
          <template v-else>
          <tr v-for="v in ventas" :key="v.id">
            <td>#{{ v.id }}</td>
            <td>{{ formatBoliviaDateTime(v.fecha_hora) }}</td>
            <td>
              <div v-for="d in v.detalles" :key="d.id" class="text-caption">
                {{ Number(d.cantidad) }} × {{ d.nombre }}
                <span class="text-grey-6">({{ money(d.total) }})</span>
              </div>
            </td>
            <td>{{ v.seguro?.nombre || 'PARTICULAR' }}</td>
            <td>{{ v.tipo_pago || '—' }}</td>
            <td>{{ v.user?.name || '—' }}</td>
            <td class="text-center">
              <q-badge :color="v.estado === 'PENDIENTE' && !v.fecha_hora_cobro ? 'orange' : 'positive'">
                {{ v.estado === 'PENDIENTE' ? (v.fecha_hora_cobro ? 'COBRADO' : 'PENDIENTE') : v.estado }}
              </q-badge>
            </td>
            <td class="text-right text-weight-bold">{{ money(v.total) }}</td>
          </tr>
          </template>
        </tbody>
        <tfoot v-if="ventas.length && hayFiltroFechas">
          <tr>
            <td colspan="7" class="text-right text-weight-bold">Total en el periodo</td>
            <td class="text-right text-weight-bold text-primary">{{ money(totalPeriodo) }} Bs</td>
          </tr>
        </tfoot>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-sm">
        <div class="text-caption text-grey-7">
          <template v-if="totalRegistros">Mostrando {{ desde }}–{{ hasta }} de {{ totalRegistros }}</template>
        </div>
        <q-pagination v-if="ultimaPagina > 1" v-model="pagina" :max="ultimaPagina" :max-pages="7"
                      direction-links boundary-links size="sm" @update:model-value="cargar" />
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
import { formatBoliviaDate, formatBoliviaDateTime } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

const paciente = ref(null)
const opcionesPaciente = ref([])
const fechaInicio = ref('')
const fechaFin = ref('')

const info = ref(null)
const ventas = ref([])
const totalPeriodo = ref(0)
const pagina = ref(1)
const ultimaPagina = ref(1)
const totalRegistros = ref(0)
const desde = ref(0)
const hasta = ref(0)
const loading = ref(false)

const hayFiltroFechas = computed(() => !!(fechaInicio.value || fechaFin.value))

async function buscarPacientes (val, update, abort) {
  if ((val || '').trim().length < 2) {
    update(() => { opcionesPaciente.value = [] })
    return
  }
  try {
    const { data } = await proxy.$axios.get('compras-pacientes/pacientes', { params: { q: val } })
    update(() => { opcionesPaciente.value = data })
  } catch {
    abort()
  }
}

function seleccionar () {
  info.value = null
  fechaInicio.value = ''
  fechaFin.value = ''
  buscar()
}

function buscar () {
  pagina.value = 1
  cargar()
}

async function cargar () {
  if (!paciente.value) {
    ventas.value = []
    info.value = null
    return
  }
  loading.value = true
  try {
    const { data } = await proxy.$axios.get(`compras-pacientes/${paciente.value.id}`, {
      params: {
        fecha_inicio: fechaInicio.value || undefined,
        fecha_fin: fechaFin.value || undefined,
        page: pagina.value,
      },
    })
    info.value = { paciente: data.paciente, resumen: data.resumen }
    ventas.value = data.ventas.data
    ultimaPagina.value = data.ventas.last_page
    totalRegistros.value = data.ventas.total
    desde.value = data.ventas.from
    hasta.value = data.ventas.to
    totalPeriodo.value = data.total_periodo
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las compras')
  } finally {
    loading.value = false
  }
}

function money (v) { return Number(v || 0).toFixed(2) }
</script>
