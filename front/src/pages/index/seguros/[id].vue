<template>
  <q-page class="q-pa-md seguro-detalle">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver seguros</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <!-- Encabezado -->
      <div class="row items-center q-mb-sm">
        <q-btn flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" to="/seguros">
          <q-tooltip>Volver a seguros</q-tooltip>
        </q-btn>
        <div>
          <div class="text-h5 text-weight-bold">{{ detalle.seguro?.nombre || 'Seguro' }}</div>
          <div class="text-body2 text-grey-6">
            NIT: {{ detalle.seguro?.nit || '—' }} · Planilla de pacientes e internaciones
          </div>
        </div>
        <q-space />
        <q-input v-model="mes" type="month" label="Mes" dense outlined style="width:150px" class="q-mr-sm"
                 @update:model-value="cargarDetalle">
          <template v-slot:append>
            <q-icon v-if="mes" name="clear" class="cursor-pointer" @click.stop="limpiarMes" />
          </template>
        </q-input>
        <q-btn outline rounded no-caps color="grey-7" icon="refresh" class="q-mr-sm" @click="cargarDetalle">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
        <q-btn outline rounded no-caps color="grey-7" icon="print" label="Imprimir" @click="imprimirDetalle" />
      </div>
      <q-separator class="q-mb-md" />

      <!-- Resumen -->
      <div class="row q-col-gutter-sm q-mb-md">
        <div v-for="item in resumenCards" :key="item.label" class="col-6 col-md-2">
          <q-card flat bordered class="q-pa-sm full-height">
            <div class="text-caption text-grey-7">{{ item.label }}</div>
            <div class="text-subtitle1 text-weight-bold" :class="item.color || 'text-primary'">{{ item.value }}</div>
          </q-card>
        </div>
      </div>

      <q-inner-loading :showing="loading" color="primary" />

      <!-- ══ PENDIENTES (arriba) ═══════════════════════════════════ -->
      <div class="row items-center q-mb-xs">
        <q-icon name="pending_actions" color="orange-8" size="20px" class="q-mr-xs" />
        <span class="text-subtitle1 text-weight-bold">Pendientes</span>
        <q-badge rounded color="orange-1" text-color="orange-9" class="q-ml-sm text-weight-bold">
          {{ pendientes.length }}
        </q-badge>
        <span class="text-caption text-grey-6 q-ml-sm">
          Falta completar la planilla del seguro
        </span>
      </div>

      <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-planilla q-mb-lg">
        <thead>
          <tr class="bg-orange-1 text-orange-10 text-uppercase">
            <th class="text-center" style="width:44px">Nº</th>
            <th class="text-left" style="min-width:180px">Paciente</th>
            <th class="text-center">Entrega de informe</th>
            <th class="text-center">Respuesta de auditoría</th>
            <th class="text-center">Fecha de facturación</th>
            <th class="text-right">Monto facturado</th>
            <th class="text-center">Fecha de cancelación</th>
            <th class="text-left">Tipo de pago</th>
            <th class="text-right">Cargos Bs</th>
            <th class="text-center" style="width:110px">Avance</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!pendientes.length">
            <td colspan="10" class="text-center text-grey-5 q-pa-md">
              {{ loading ? 'Cargando…' : 'No hay internaciones pendientes' }}
            </td>
          </tr>
          <tr v-for="(row, idx) in pendientesPagina" :key="row.id" class="fila-clickable"
              @click="abrirSeguimiento(row)">
            <td class="text-center">{{ nroFila(pagePendientes, perPendientes, idx) }}</td>
            <td>
              <div class="text-weight-medium">{{ row.paciente?.nombre_completo || '—' }}</div>
              <div class="text-caption text-grey-6">
                CI: {{ row.paciente?.ci || '—' }} · Ingreso: {{ row.fecha_ingreso || '—' }}
              </div>
            </td>
            <td class="text-center" :class="faltaClass(row.entrega_informe)">{{ fecha(row.entrega_informe) }}</td>
            <td class="text-center" :class="faltaClass(row.respuesta_auditoria)">{{ fecha(row.respuesta_auditoria) }}</td>
            <td class="text-center" :class="faltaClass(row.fecha_facturacion)">{{ fecha(row.fecha_facturacion) }}</td>
            <td class="text-right" :class="faltaClass(row.monto_facturado)">
              {{ row.monto_facturado !== null && row.monto_facturado !== undefined ? money(row.monto_facturado) : '—' }}
            </td>
            <td class="text-center" :class="faltaClass(row.fecha_cancelacion)">{{ fecha(row.fecha_cancelacion) }}</td>
            <td :class="faltaClass(row.tipo_pago)">{{ row.tipo_pago || '—' }}</td>
            <td class="text-right text-grey-8">{{ money(totalCargos(row)) }}</td>
            <td class="text-center">
              <q-badge rounded color="orange-1" text-color="orange-9" class="text-weight-bold">
                {{ row.seguimiento_llenados }}/{{ TOTAL_REQUISITOS }}
              </q-badge>
              <q-btn v-if="canEditar" flat dense round size="sm" icon="edit" color="primary"
                     @click.stop="abrirSeguimiento(row)">
                <q-tooltip>Llenar planilla</q-tooltip>
              </q-btn>
            </td>
          </tr>
        </tbody>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-xs q-mb-lg q-px-xs">
        <div class="row items-center q-gutter-sm">
          <span class="text-caption text-grey-6">
            {{ pendientes.length }} pendiente(s) · Página {{ pagePendientes }} de {{ pagesPendientes }}
          </span>
          <q-select v-model="perPendientes" :options="OPCIONES_POR_PAGINA" dense outlined
                    style="width:82px" @update:model-value="pagePendientes = 1" />
        </div>
        <q-pagination v-model="pagePendientes" :max="pagesPendientes" :max-pages="6"
                      boundary-links direction-links size="sm" color="orange-8" />
      </div>

      <!-- ══ COMPLETADOS (abajo) ═══════════════════════════════════ -->
      <div class="row items-center q-mb-xs">
        <q-icon name="task_alt" color="positive" size="20px" class="q-mr-xs" />
        <span class="text-subtitle1 text-weight-bold">Completados</span>
        <q-badge rounded color="green-1" text-color="positive" class="q-ml-sm text-weight-bold">
          {{ completados.length }}
        </q-badge>
        <span class="text-caption text-grey-6 q-ml-sm">
          Planilla llena: informe, auditoría, facturación, cancelación y tipo de pago
        </span>
      </div>

      <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-planilla q-mb-lg">
        <thead>
          <tr class="bg-green-1 text-green-10 text-uppercase">
            <th class="text-center" style="width:44px">Nº</th>
            <th class="text-left" style="min-width:180px">Paciente</th>
            <th class="text-center">Entrega de informe</th>
            <th class="text-center">Respuesta de auditoría</th>
            <th class="text-center">Fecha de facturación</th>
            <th class="text-right">Monto facturado</th>
            <th class="text-center">Fecha de cancelación</th>
            <th class="text-left">Tipo de pago</th>
            <th class="text-right">Cargos Bs</th>
            <th class="text-center" style="width:110px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!completados.length">
            <td colspan="10" class="text-center text-grey-5 q-pa-md">
              {{ loading ? 'Cargando…' : 'Todavía no hay internaciones completadas' }}
            </td>
          </tr>
          <tr v-for="(row, idx) in completadosPagina" :key="row.id" class="fila-clickable"
              @click="abrirSeguimiento(row)">
            <td class="text-center">{{ nroFila(pageCompletados, perCompletados, idx) }}</td>
            <td>
              <div class="text-weight-medium">{{ row.paciente?.nombre_completo || '—' }}</div>
              <div class="text-caption text-grey-6">
                CI: {{ row.paciente?.ci || '—' }} · Ingreso: {{ row.fecha_ingreso || '—' }}
              </div>
            </td>
            <td class="text-center">{{ fecha(row.entrega_informe) }}</td>
            <td class="text-center">{{ fecha(row.respuesta_auditoria) }}</td>
            <td class="text-center">{{ fecha(row.fecha_facturacion) }}</td>
            <td class="text-right text-weight-bold">{{ money(row.monto_facturado) }}</td>
            <td class="text-center">{{ fecha(row.fecha_cancelacion) }}</td>
            <td>{{ row.tipo_pago || '—' }}</td>
            <td class="text-right text-grey-8">{{ money(totalCargos(row)) }}</td>
            <td class="text-center">
              <q-badge rounded color="green-1" text-color="positive" class="text-weight-bold">OK</q-badge>
              <q-btn v-if="canEditar" flat dense round size="sm" icon="edit" color="primary"
                     @click.stop="abrirSeguimiento(row)">
                <q-tooltip>Editar planilla</q-tooltip>
              </q-btn>
            </td>
          </tr>
        </tbody>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-xs q-mb-lg q-px-xs">
        <div class="row items-center q-gutter-sm">
          <span class="text-caption text-grey-6">
            {{ completados.length }} completado(s) · Página {{ pageCompletados }} de {{ pagesCompletados }}
          </span>
          <q-select v-model="perCompletados" :options="OPCIONES_POR_PAGINA" dense outlined
                    style="width:82px" @update:model-value="pageCompletados = 1" />
        </div>
        <q-pagination v-model="pageCompletados" :max="pagesCompletados" :max-pages="6"
                      boundary-links direction-links size="sm" color="positive" />
      </div>

      <!-- ══ PACIENTES AFILIADOS ═══════════════════════════════════ -->
      <div class="row items-center q-mb-xs">
        <q-icon name="groups" color="primary" size="20px" class="q-mr-xs" />
        <span class="text-subtitle1 text-weight-bold">Pacientes afiliados</span>
        <q-badge rounded color="teal-1" text-color="primary" class="q-ml-sm text-weight-bold">
          {{ (detalle.pacientes || []).length }}
        </q-badge>
      </div>

      <q-table dense flat bordered row-key="id" :rows="detalle.pacientes || []"
               :columns="pacienteColumns" v-model:pagination="paginationPacientes"
               :rows-per-page-options="OPCIONES_POR_PAGINA"
               class="rounded-borders"
               rows-per-page-label="Por página"
               no-data-label="No hay pacientes afiliados actualmente" />

    </template>

    <!-- ══ DIALOG PLANILLA DE SEGUIMIENTO ═══════════════════════════ -->
    <q-dialog v-model="dialogSeguimiento" persistent>
      <q-card style="width:min(96vw,560px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="fact_check" size="20px" class="q-mr-sm" />
          <div class="column">
            <span class="text-subtitle1 text-weight-bold">Planilla del seguro</span>
            <span class="text-caption">{{ seguimiento.paciente?.nombre_completo || '—' }}</span>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogSeguimiento = false" />
        </q-card-section>

        <q-card-section style="padding:14px 16px;max-height:74vh;overflow-y:auto">
          <div class="text-caption text-grey-7 q-mb-sm">
            La internación pasa a <b>Completados</b> cuando los seis campos estén llenos.
            Cargos registrados: <b>{{ money(totalCargos(seguimiento)) }} Bs</b>.
          </div>

          <q-form @submit.prevent="guardarSeguimiento">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input v-model="seguimiento.entrega_informe" label="Entrega de informe" dense outlined type="date" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="seguimiento.respuesta_auditoria" label="Respuesta de auditoría" dense outlined type="date" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="seguimiento.fecha_facturacion" label="Fecha de facturación" dense outlined type="date" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model.number="seguimiento.monto_facturado" label="Monto facturado (Bs)" dense outlined
                         type="number" step="0.01" min="0">
                  <template v-slot:append>
                    <q-btn flat dense round size="sm" icon="functions" color="primary"
                           @click="seguimiento.monto_facturado = Number(totalCargos(seguimiento).toFixed(2))">
                      <q-tooltip>Usar el total de cargos de la internación</q-tooltip>
                    </q-btn>
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="seguimiento.fecha_cancelacion" label="Fecha de cancelación" dense outlined type="date" />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="seguimiento.tipo_pago" label="Tipo de pago" dense outlined clearable
                          :options="TIPOS_PAGO" />
              </div>
              <div class="col-12">
                <q-input v-model="seguimiento.observacion_seguro" label="Observación" dense outlined
                         type="textarea" rows="2" />
              </div>
            </div>

            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogSeguimiento = false" />
              <q-btn color="primary" label="Guardar planilla" type="submit" no-caps
                     :loading="guardando" icon-right="save" :disable="!canEditar" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { useRoute } from 'vue-router'
import { imprimirSeguro } from '../../../addons/seguroPrint'

const { proxy } = getCurrentInstance()
const route = useRoute()

const canVer    = computed(() => proxy.$store.hasPermission('Ver Seguros'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Seguros'))

// Requisitos que exige la planilla del seguro (los mismos que valida el backend).
const TOTAL_REQUISITOS = 6
const TIPOS_PAGO = ['DEPOSITO', 'CHEQUE', 'EFECTIVO', 'TRANSFERENCIA']

const seguroId = computed(() => route.params.id)

const detalle = ref({ seguro: {}, pacientes: [], internaciones: [], resumen: {} })
const loading = ref(false)
const mes     = ref('')

function money (v) { return Number(v || 0).toFixed(2) }
function fecha (v) { return v ? String(v).slice(0, 10) : '—' }
function faltaClass (v) {
  return (v === null || v === undefined || v === '') ? 'text-grey-4' : ''
}
function totalCargos (row) {
  return (row?.items || []).reduce((sum, item) => sum + Number(item.total || 0), 0)
}

// Pendientes arriba, completados abajo: el backend marca el estado de cada fila.
const pendientes  = computed(() =>
  (detalle.value.internaciones || []).filter(i => i.seguimiento_estado !== 'COMPLETADO'))
const completados = computed(() =>
  (detalle.value.internaciones || []).filter(i => i.seguimiento_estado === 'COMPLETADO'))

// ── Paginación (en el cliente: el detalle llega completo en una sola llamada) ──
const OPCIONES_POR_PAGINA = [10, 20, 50, 100]

const pagePendientes  = ref(1)
const perPendientes   = ref(10)
const pageCompletados = ref(1)
const perCompletados  = ref(10)
const paginationPacientes = ref({ rowsPerPage: 10 })

function paginar (filas, page, per) {
  const inicio = (page - 1) * per
  return filas.slice(inicio, inicio + per)
}

function paginas (filas, per) {
  return Math.max(1, Math.ceil(filas.length / per))
}

// La planilla va numerada de corrido, no reinicia el Nº en cada página.
function nroFila (page, per, idx) {
  return (page - 1) * per + idx + 1
}

const pagesPendientes    = computed(() => paginas(pendientes.value, perPendientes.value))
const pagesCompletados   = computed(() => paginas(completados.value, perCompletados.value))
const pendientesPagina   = computed(() => paginar(pendientes.value, pagePendientes.value, perPendientes.value))
const completadosPagina  = computed(() => paginar(completados.value, pageCompletados.value, perCompletados.value))

// Al recargar o al mover filas entre tablas, la página actual puede quedar vacía.
watch(pagesPendientes, (max) => { if (pagePendientes.value > max) pagePendientes.value = max })
watch(pagesCompletados, (max) => { if (pageCompletados.value > max) pageCompletados.value = max })

const pacienteColumns = [
  { name: 'nombre', label: 'Paciente', align: 'left', field: 'nombre_completo', sortable: true },
  { name: 'ci', label: 'CI', align: 'left', field: row => row.ci || '—', sortable: true },
  { name: 'telefono', label: 'Teléfono', align: 'left', field: row => row.telefono || '—' },
]

const resumenCards = computed(() => {
  const r = detalle.value.resumen || {}
  return [
    { label: 'Pacientes afiliados', value: r.cantidad_pacientes || 0 },
    { label: 'Internaciones', value: r.cantidad_internaciones || 0 },
    { label: 'Pendientes', value: r.pendientes || 0, color: 'text-orange-8' },
    { label: 'Completados', value: r.completados || 0, color: 'text-positive' },
    { label: 'Total cargos', value: `${money(r.total)} Bs` },
    { label: 'Total facturado', value: `${money(r.total_facturado)} Bs` },
  ]
})

// ── Carga ────────────────────────────────────────────────────────
async function cargarDetalle () {
  if (!seguroId.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('seguros/' + seguroId.value + '/detalle', {
      params: { mes: mes.value || undefined },
    })
    detalle.value = data
    pagePendientes.value = 1
    pageCompletados.value = 1
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar el detalle del seguro')
  } finally {
    loading.value = false
  }
}

function limpiarMes () {
  mes.value = ''
  cargarDetalle()
}

// ── Planilla ─────────────────────────────────────────────────────
const dialogSeguimiento = ref(false)
const seguimiento = ref({})
const guardando = ref(false)

function abrirSeguimiento (row) {
  if (!canEditar.value) return
  seguimiento.value = {
    ...row,
    entrega_informe: fechaInput(row.entrega_informe),
    respuesta_auditoria: fechaInput(row.respuesta_auditoria),
    fecha_facturacion: fechaInput(row.fecha_facturacion),
    fecha_cancelacion: fechaInput(row.fecha_cancelacion),
    monto_facturado: row.monto_facturado !== null && row.monto_facturado !== undefined
      ? Number(row.monto_facturado)
      : null,
  }
  dialogSeguimiento.value = true
}

// Los <input type="date"> solo aceptan YYYY-MM-DD.
function fechaInput (v) { return v ? String(v).slice(0, 10) : '' }

async function guardarSeguimiento () {
  guardando.value = true
  try {
    await proxy.$axios.put('internaciones/' + seguimiento.value.id + '/seguimiento', {
      entrega_informe: seguimiento.value.entrega_informe || null,
      respuesta_auditoria: seguimiento.value.respuesta_auditoria || null,
      fecha_facturacion: seguimiento.value.fecha_facturacion || null,
      monto_facturado: seguimiento.value.monto_facturado ?? null,
      fecha_cancelacion: seguimiento.value.fecha_cancelacion || null,
      tipo_pago: seguimiento.value.tipo_pago || null,
      observacion_seguro: seguimiento.value.observacion_seguro || null,
    })
    dialogSeguimiento.value = false
    proxy.$alert.success('Planilla guardada')
    cargarDetalle()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar la planilla')
  } finally {
    guardando.value = false
  }
}

function imprimirDetalle () {
  imprimirSeguro(detalle.value)
}

watch(() => proxy.$store.isLogged, (val) => { if (val) cargarDetalle() }, { immediate: true })
watch(seguroId, () => { if (proxy.$store.isLogged) cargarDetalle() })
</script>

<style scoped>
.seguro-detalle :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.seguro-detalle :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 32px;
  min-height: 32px;
}

.tabla-planilla :deep(th),
.tabla-planilla :deep(td) {
  font-size: 11px;
  padding: 4px 8px;
}

.tabla-planilla :deep(th) {
  font-weight: 700;
  white-space: normal;
  line-height: 1.15;
}

.fila-clickable { cursor: pointer; }
.fila-clickable:hover { background: #f1f5f9; }
</style>
