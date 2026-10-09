<template>
  <q-page class="q-pa-sm">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Ventas de clientes</div>
        <div class="text-caption text-grey-6">Busque un cliente y vea lo que compró entre dos fechas</div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="row q-col-gutter-sm items-center q-mb-sm">
      <div class="col-12 col-sm-5">
        <q-select v-model="cliente" dense outlined clearable use-input hide-bottom-space
                  input-debounce="350" :options="opcionesCliente" option-label="nombre"
                  label="Cliente (nombre o CI)" @filter="buscarClientes" @update:model-value="buscar">
          <template v-slot:prepend><q-icon name="person_search" /></template>
          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section>
                <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                <q-item-label caption>
                  {{ scope.opt.tipo === 'PACIENTE' ? 'Paciente' : 'Cliente sin registro' }}
                  <template v-if="scope.opt.ci"> · CI {{ scope.opt.ci }}</template>
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <template v-slot:no-option>
            <q-item><q-item-section class="text-grey">Escriba al menos 2 letras</q-item-section></q-item>
          </template>
        </q-select>
      </div>
      <div class="col-6 col-sm-2">
        <q-input v-model="fechaInicio" type="date" label="Desde" dense outlined stack-label
                 hide-bottom-space @update:model-value="buscar" />
      </div>
      <div class="col-6 col-sm-2">
        <q-input v-model="fechaFin" type="date" label="Hasta" dense outlined stack-label
                 hide-bottom-space @update:model-value="buscar" />
      </div>
    </div>

    <div v-if="!cliente" class="column items-center q-pa-xl text-grey-6">
      <q-icon name="person_search" size="56px" color="grey-4" />
      <div class="q-mt-sm">Seleccione un cliente para ver su historial</div>
    </div>

    <template v-else>
      <q-markup-table dense flat bordered wrap-cells>
        <thead class="bg-grey-2">
          <tr>
            <th class="text-left" style="width:130px">Fecha</th>
            <th class="text-left">Detalle</th>
            <th class="text-left" style="width:130px">Seguro</th>
            <th class="text-left" style="width:100px">Pago</th>
            <th class="text-center" style="width:90px">Estado</th>
            <th class="text-right" style="width:90px">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="text-center"><q-spinner color="primary" /></td></tr>
          <tr v-else-if="!ventas.length"><td colspan="6" class="text-center text-grey-6">Sin ventas en estas fechas</td></tr>
          <template v-else>
          <tr v-for="v in ventas" :key="v.id">
            <td>{{ formatBoliviaDateTime(v.fecha_hora) }}</td>
            <td>
              <div v-for="d in v.detalles" :key="d.id" class="text-caption">
                {{ Number(d.cantidad) }} × {{ d.nombre }}
                <span class="text-grey-6">({{ money(d.total) }})</span>
              </div>
            </td>
            <td>{{ v.seguro?.nombre || 'PARTICULAR' }}</td>
            <td>{{ v.tipo_pago || '—' }}</td>
            <td class="text-center">
              <q-badge :color="v.estado === 'PENDIENTE' && !v.fecha_hora_cobro ? 'orange' : 'positive'">
                {{ v.estado === 'PENDIENTE' ? (v.fecha_hora_cobro ? 'COBRADO' : 'PENDIENTE') : v.estado }}
              </q-badge>
            </td>
            <td class="text-right text-weight-bold">{{ money(v.total) }}</td>
          </tr>
          </template>
        </tbody>
        <tfoot v-if="ventas.length">
          <tr>
            <td colspan="5" class="text-right text-weight-bold">Total del cliente en el periodo</td>
            <td class="text-right text-weight-bold text-primary">{{ money(total) }} Bs</td>
          </tr>
        </tfoot>
      </q-markup-table>

      <div v-if="ultimaPagina > 1" class="row justify-center q-mt-sm">
        <q-pagination v-model="pagina" :max="ultimaPagina" :max-pages="7" direction-links boundary-links
                      size="sm" @update:model-value="cargar" />
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { getCurrentInstance, ref } from 'vue'
import { formatBoliviaDateTime } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

function hoy () {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const cliente = ref(null)
const opcionesCliente = ref([])
const fechaInicio = ref(hoy().slice(0, 8) + '01')
const fechaFin = ref(hoy())

const ventas = ref([])
const total = ref(0)
const pagina = ref(1)
const ultimaPagina = ref(1)
const loading = ref(false)

async function buscarClientes (val, update, abort) {
  if ((val || '').trim().length < 2) {
    update(() => { opcionesCliente.value = [] })
    return
  }
  try {
    const { data } = await proxy.$axios.get('ventas-clientes/clientes', { params: { q: val } })
    update(() => { opcionesCliente.value = data })
  } catch {
    abort()
  }
}

function buscar () {
  pagina.value = 1
  cargar()
}

async function cargar () {
  if (!cliente.value) {
    ventas.value = []
    return
  }
  loading.value = true
  try {
    const params = {
      fecha_inicio: fechaInicio.value || undefined,
      fecha_fin: fechaFin.value || undefined,
      page: pagina.value,
    }
    if (cliente.value.tipo === 'PACIENTE') params.paciente_id = cliente.value.paciente_id
    else params.cliente = cliente.value.cliente

    const { data } = await proxy.$axios.get('ventas-clientes', { params })
    ventas.value = data.ventas.data
    ultimaPagina.value = data.ventas.last_page
    total.value = data.total
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las ventas')
  } finally {
    loading.value = false
  }
}

function money (v) { return Number(v || 0).toFixed(2) }
</script>
