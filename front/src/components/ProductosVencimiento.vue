<template>
  <q-page class="q-pa-md productos-vencimiento">
    <div v-if="$store.isLogged && checkingPermissions"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-spinner-dots color="primary" size="42px" />
      <div class="text-body2 text-grey-6">Verificando permisos…</div>
    </div>

    <div v-else-if="$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para consultar esta opción</div>
    </div>

    <template v-else-if="$store.isLogged">
      <div class="row items-center q-col-gutter-sm q-mb-md">
        <div class="col">
          <div class="text-h5 text-weight-bold">{{ titulo }}</div>
          <div class="text-body2 text-grey-6">{{ subtitulo }}</div>
        </div>
        <div class="col-auto">
          <q-btn round flat color="primary" icon="refresh" :loading="loading" @click="cargar">
            <q-tooltip>Actualizar</q-tooltip>
          </q-btn>
        </div>
      </div>

      <div class="row q-col-gutter-sm q-mb-md">
        <div class="col-12 col-sm-4">
          <q-card flat bordered class="q-pa-sm rounded-borders">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Lotes encontrados</div>
            <div class="text-h5 text-weight-bold">{{ pagination.rowsNumber }}</div>
          </q-card>
        </div>
        <div class="col-12 col-sm-4">
          <q-card flat bordered class="q-pa-sm rounded-borders">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Existencia visible</div>
            <div class="text-h5 text-weight-bold text-primary">{{ numero(existenciaPagina) }}</div>
          </q-card>
        </div>
      </div>

      <q-card flat bordered class="rounded-borders">
        <q-card-section class="q-pa-sm">
          <div class="row items-center q-col-gutter-xs">
            <div class="col-12 col-sm">
              <q-input v-model="filtros.q" dense outlined clearable debounce="400"
                       placeholder="Buscar producto, código, lote, factura o proveedor"
                       @update:model-value="buscar">
                <template #prepend><q-icon name="search" /></template>
              </q-input>
            </div>
            <template v-if="tipo === 'por-vencer'">
              <div class="col-6 col-sm-auto">
                <q-input v-model.number="filtros.valor" dense outlined type="number" min="1"
                         label="Próximos" style="width:120px" @update:model-value="buscar" />
              </div>
              <div class="col-6 col-sm-auto">
                <q-select v-model="filtros.unidad" dense outlined emit-value map-options
                          :options="unidadesTiempo" label="Unidad" style="width:130px"
                          @update:model-value="buscar" />
              </div>
            </template>
            <div v-else class="col-12 col-sm-auto">
              <q-input v-model="filtros.hasta" dense outlined type="date" label="Vencidos antes de"
                       style="width:175px" @update:model-value="buscar" />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-table flat dense row-key="id" :rows="rows" :columns="columns"
                 :loading="loading" v-model:pagination="pagination"
                 :rows-per-page-options="[10, 15, 25, 50]"
                 no-data-label="No se encontraron lotes"
                 @request="onRequest">
          <template #body-cell-vencimiento="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ fecha(props.row.fecha_vencimiento) }}</div>
              <q-chip dense square :color="chipColor(props.row)" text-color="white" size="sm">
                {{ textoDias(props.row) }}
              </q-chip>
            </q-td>
          </template>
          <template #body-cell-producto="props">
            <q-td :props="props">
              <div class="text-weight-bold">{{ props.row.producto?.nombre || props.row.nombre }}</div>
              <div class="text-caption text-grey-7">
                {{ props.row.producto?.codigo || 'S/C' }} · Lote: {{ props.row.lote || 'S/L' }}
              </div>
            </q-td>
          </template>
          <template #body-cell-existencia="props">
            <q-td :props="props">
              <q-chip dense color="primary" text-color="white" class="text-weight-bold">
                {{ numero(props.row.existencia) }}
              </q-chip>
              <span class="text-caption text-grey-7">{{ props.row.producto?.unidad?.nombre || '' }}</span>
            </q-td>
          </template>
          <template #body-cell-compra="props">
            <q-td :props="props">
              <div>{{ props.row.compra?.nro_factura || 'Sin factura' }}</div>
              <div class="text-caption text-grey-7">{{ fecha(props.row.compra?.fecha_hora) }}</div>
            </q-td>
          </template>
          <template #body-cell-origen="props">
            <q-td :props="props">
              <div>{{ props.row.compra?.proveedor?.nombre || 'Sin proveedor' }}</div>
              <div class="text-caption text-grey-7">{{ props.row.compra?.user?.name || props.row.compra?.user?.username || '' }}</div>
            </q-td>
          </template>
        </q-table>
      </q-card>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, reactive, ref } from 'vue'

const props = defineProps({
  tipo: {
    type: String,
    required: true,
    validator: value => ['por-vencer', 'vencidos'].includes(value),
  },
})

const { proxy } = getCurrentInstance()
const loading = ref(false)
const checkingPermissions = ref(true)
const permissionGranted = ref(false)
const rows = ref([])
const filtros = reactive({
  q: '',
  valor: 30,
  unidad: 'DIAS',
  hasta: new Date().toISOString().slice(0, 10),
})
const pagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })
const unidadesTiempo = [
  { label: 'Días', value: 'DIAS' },
  { label: 'Meses', value: 'MESES' },
  { label: 'Años', value: 'ANIOS' },
]
const columns = [
  { name: 'vencimiento', label: 'Vencimiento', field: 'fecha_vencimiento', align: 'left' },
  { name: 'producto', label: 'Producto / lote', field: row => row.producto?.nombre, align: 'left' },
  { name: 'existencia', label: 'Existencia', field: 'existencia', align: 'left' },
  { name: 'cantidad', label: 'Comprada', field: 'cantidad', align: 'right', format: numero },
  { name: 'vendida', label: 'Vendida', field: 'cantidad_vendida', align: 'right', format: numero },
  { name: 'compra', label: 'Compra', field: row => row.compra?.nro_factura, align: 'left' },
  { name: 'origen', label: 'Proveedor / usuario', field: row => row.compra?.proveedor?.nombre, align: 'left' },
]

const permiso = computed(() => props.tipo === 'por-vencer' ? 'Ver Productos por Vencer' : 'Ver Productos Vencidos')
const canVer = computed(() => permissionGranted.value || proxy.$store.hasPermission(permiso.value))
const titulo = computed(() => props.tipo === 'por-vencer' ? 'Productos por vencer' : 'Productos vencidos')
const subtitulo = computed(() => props.tipo === 'por-vencer'
  ? 'Lotes de farmacia próximos a su fecha de vencimiento'
  : 'Lotes de farmacia que superaron su fecha de vencimiento')
const existenciaPagina = computed(() => rows.value.reduce((total, row) => total + Number(row.existencia || 0), 0))
const endpoint = computed(() => props.tipo === 'por-vencer' ? '/productos-por-vencer' : '/productos-vencidos')

onMounted(async () => {
  permissionGranted.value = proxy.$store.hasPermission(permiso.value)
  checkingPermissions.value = false
  if (permissionGranted.value) await cargar()
})

function buscar () {
  pagination.value.page = 1
  cargar()
}

function onRequest (request) {
  pagination.value = request.pagination
  cargar()
}

async function cargar (verificandoAcceso = false) {
  if (!verificandoAcceso && !canVer.value) return
  loading.value = true
  try {
    const params = {
      q: filtros.q || undefined,
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    }
    if (props.tipo === 'por-vencer') {
      params.valor = filtros.valor || 30
      params.unidad = filtros.unidad
    } else {
      params.hasta = filtros.hasta
    }
    const { data } = await proxy.$axios.get(endpoint.value, { params })
    permissionGranted.value = true
    rows.value = data.data || []
    pagination.value.rowsNumber = data.total || 0
    pagination.value.page = data.current_page || 1
  } catch (error) {
    if (error.response?.status === 403) permissionGranted.value = false
    else proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar la información')
  } finally {
    loading.value = false
  }
}

function fecha (value) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('es-BO').format(new Date(String(value).slice(0, 10) + 'T00:00:00'))
}

function numero (value) {
  return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 4 }).format(Number(value || 0))
}

function textoDias (row) {
  const dias = Number(row.dias_vencimiento || 0)
  if (dias < 0) return `Venció hace ${Math.abs(dias)} día${Math.abs(dias) === 1 ? '' : 's'}`
  if (dias === 0) return 'Vence hoy'
  return `${dias} día${dias === 1 ? '' : 's'}`
}

function chipColor (row) {
  const dias = Number(row.dias_vencimiento || 0)
  if (dias < 0) return 'negative'
  if (dias <= 7) return 'orange-9'
  return 'warning'
}
</script>

<style scoped>
.productos-vencimiento :deep(.q-table th) {
  font-size: 11px;
  text-transform: uppercase;
  color: #616161;
}

.productos-vencimiento :deep(.q-table td) {
  height: 46px;
}
</style>
