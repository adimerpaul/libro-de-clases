<template>
  <q-page class="q-pa-sm sol-lista">
    <!-- Cabecera y filtros en una sola línea -->
    <div class="row items-center no-wrap q-gutter-xs q-mb-xs">
      <div>
        <div class="text-subtitle1 text-weight-bold">Laboratorios creados</div>
        <div class="sol-sub text-grey-6">Solicitudes registradas para pacientes</div>
      </div>
      <q-space />
      <q-input v-model="filters.q" dense outlined clearable debounce="400" style="width:230px"
               placeholder="Código, paciente o CI" @update:model-value="recargar">
        <template #prepend><q-icon name="search" size="16px" /></template>
      </q-input>
      <q-select v-model="filters.estado" dense outlined clearable style="width:160px"
                :options="estados" label="Estado" @update:model-value="recargar" />
      <q-input v-model="filters.desde" dense outlined type="date" label="Desde" style="width:135px"
               @update:model-value="recargar" />
      <q-input v-model="filters.hasta" dense outlined type="date" label="Hasta" style="width:135px"
               @update:model-value="recargar" />
      <q-badge color="primary">{{ pagination.rowsNumber }}</q-badge>
      <q-btn v-if="canCrear" dense unelevated rounded color="positive" icon="add" label="Crear laboratorio"
             no-caps size="11px" to="/solicitudes-laboratorio/nueva" />
      <q-btn flat round dense color="primary" icon="refresh" :loading="loading" @click="recargar">
        <q-tooltip>Actualizar</q-tooltip>
      </q-btn>
    </div>

    <q-table ref="tableRef" flat bordered dense row-key="id" class="tabla-compacta"
             :rows="rows" :columns="columns" :loading="loading"
             v-model:pagination="pagination" :rows-per-page-options="[15, 25, 50, 100]"
             @request="onRequest">
      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-badge :color="estadoColor(props.row.estado)">{{ props.row.estado }}</q-badge>
        </q-td>
      </template>
      <template #body-cell-codigo="props">
        <q-td :props="props"><q-badge outline color="primary">{{ props.row.codigo_solicitud }}</q-badge></q-td>
      </template>
      <template #body-cell-pruebas="props">
        <q-td :props="props" class="sol-pruebas">
          <!-- Se muestran dos y el resto se cuenta, para no estirar la fila -->
          <q-badge v-for="item in props.row.laboratorio_items.slice(0, 2)" :key="item.id"
                   color="grey-3" text-color="grey-9" class="q-mr-xs">
            {{ item.producto_nombre }}
          </q-badge>
          <q-badge v-if="props.row.laboratorio_items.length > 2" color="grey-6">
            +{{ props.row.laboratorio_items.length - 2 }}
            <q-tooltip>
              <div v-for="item in props.row.laboratorio_items.slice(2)" :key="item.id">
                {{ item.producto_nombre }}
              </div>
            </q-tooltip>
          </q-badge>
        </q-td>
      </template>
      <template #body-cell-total="props">
        <q-td :props="props" class="text-weight-bold">Bs {{ money(props.row.total) }}</q-td>
      </template>
      <template #body-cell-opciones="props">
        <q-td :props="props" class="q-pa-xs">
          <q-btn-dropdown dense unelevated rounded color="primary" label="Opciones" no-caps size="10px">
            <q-list dense style="min-width:200px">
              <q-item clickable v-close-popup @click="verDetalle(props.row)">
                <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                <q-item-section>Ver detalle</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="imprimir(props.row)">
                <q-item-section avatar><q-icon name="print" color="indigo" /></q-item-section>
                <q-item-section>Imprimir / reimprimir</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="imprimirRotulo(props.row)">
                <q-item-section avatar><q-icon name="label" color="teal" /></q-item-section>
                <q-item-section>Imprimir rótulo</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="enviarWhatsApp(props.row)">
                <q-item-section avatar><q-icon name="chat" color="positive" /></q-item-section>
                <q-item-section>
                  <q-item-label>Enviar por WhatsApp</q-item-label>
                  <q-item-label caption>{{ props.row.paciente?.telefono || 'PACIENTE SIN TELÉFONO' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="canEditar && props.row.estado === 'CREADO'" clickable v-close-popup
                      @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                <q-item-section>Modificar</q-item-section>
              </q-item>
              <q-item v-if="canEliminar && props.row.estado === 'CREADO'" clickable v-close-popup
                      @click="eliminar(props.row)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="detalleDialog">
      <q-card style="width:min(94vw,850px);max-width:850px">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="biotech" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">{{ detalle?.codigo_solicitud }}</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section v-if="detalle" class="q-pa-sm">
          <div class="row q-col-gutter-xs q-mb-xs sol-datos">
            <div class="col-6 col-sm-3"><span class="text-grey-6">Paciente</span><br>{{ detalle.paciente?.nombre_completo }}</div>
            <div class="col-6 col-sm-3"><span class="text-grey-6">Doctor</span><br>{{ detalle.doctor?.nombre || 'NO ASIGNADO' }}</div>
            <div class="col-6 col-sm-3"><span class="text-grey-6">Fecha</span><br>{{ detalle.fecha_solicitud }} {{ detalle.hora_solicitud }}</div>
            <div class="col-6 col-sm-3">
              <span class="text-grey-6">Estado</span><br>
              <q-badge :color="estadoColor(detalle.estado)">{{ detalle.estado }}</q-badge>
            </div>
          </div>
          <q-list bordered separator dense>
            <q-expansion-item v-for="item in detalle.laboratorio_items" :key="item.id"
                              dense dense-toggle
                              icon="biotech" :label="item.producto_nombre"
                              :caption="`${item.resultados?.length || 0} datos · Bs ${money(item.precio)}`">
              <q-markup-table dense flat class="tabla-compacta">
                <tbody>
                  <tr v-for="resultado in item.resultados" :key="resultado.id">
                    <td>{{ resultado.nombre }}</td>
                    <td class="text-weight-bold">{{ resultado.valor || '—' }} {{ resultado.unidad || '' }}</td>
                    <td class="text-grey-7">
                      Ref.: <span v-if="resultado.rango_referencia" class="html-rango" v-html="resultado.rango_referencia_html" />
                      <template v-else>—</template>
                    </td>
                  </tr>
                </tbody>
              </q-markup-table>
            </q-expansion-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
import { nowBoliviaDateTimeInput } from '../../../addons/dateTime'
import { imprimirRotuloSolicitudLaboratorio } from '../../../addons/solicitudLaboratorioRotuloPrint'
import { imprimirSolicitudLaboratorio } from '../../../addons/solicitudLaboratorioPrint'

const { proxy } = getCurrentInstance()
const tableRef = ref(null)
const rows = ref([])
const loading = ref(false)
const detalle = ref(null)
const detalleDialog = ref(false)
const hoy = nowBoliviaDateTimeInput().slice(0, 10)
const filters = ref({ q: '', estado: null, desde: hoy, hasta: hoy })
const estados = ['CREADO', 'ATENDIENDO', 'ENVIADO_ANALITICA', 'ANALIZADO', 'FINALIZADO']
const pagination = ref({ page: 1, rowsPerPage: 25, rowsNumber: 0 })
const canCrear = computed(() => proxy.$store.hasPermission('Crear Solicitudes Laboratorio'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Solicitudes Laboratorio'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Solicitudes Laboratorio'))

const columns = [
  { name: 'opciones', label: 'Opciones', field: 'id', align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo_solicitud', align: 'left' },
  { name: 'fecha', label: 'Fecha', field: 'fecha_solicitud', align: 'left' },
  { name: 'paciente', label: 'Paciente', field: row => row.paciente?.nombre_completo, align: 'left' },
  { name: 'doctor', label: 'Doctor', field: row => row.doctor?.nombre || 'NO ASIGNADO', align: 'left' },
  { name: 'pruebas', label: 'Laboratorios', field: 'laboratorio_items', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'total', label: 'Total', field: 'total', align: 'right' },
]

function recargar () {
  tableRef.value?.requestServerInteraction()
}
function onRequest ({ pagination: requested }) {
  cargar(requested)
}
async function cargar (requested = pagination.value) {
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio', {
      params: {
        page: requested.page,
        per_page: requested.rowsPerPage,
        ...filters.value,
      },
    })
    rows.value = data.data
    pagination.value = {
      page: data.current_page,
      rowsPerPage: data.per_page,
      rowsNumber: data.total,
    }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las solicitudes')
  } finally {
    loading.value = false
  }
}
async function verDetalle (row) {
  try {
    const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${row.id}`)
    detalle.value = data
    detalleDialog.value = true
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar el detalle')
  }
}
function editar (row) {
  proxy.$router.push({ path: '/solicitudes-laboratorio/nueva', query: { id: row.id } })
}
async function imprimir (row) {
  try {
    await imprimirSolicitudLaboratorio(proxy.$axios, row.id)
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo abrir la impresión')
  }
}
async function imprimirRotulo (row) {
  try {
    const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${row.id}`)
    imprimirRotuloSolicitudLaboratorio(data, proxy.$imgBase)
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo imprimir el rótulo')
  }
}
async function enviarWhatsApp (row) {
  const telefono = normalizarTelefono(row.paciente?.telefono)
  if (!telefono) {
    proxy.$alert.error('El paciente no tiene un número de teléfono válido')
    return
  }

  const ventanaWhatsApp = window.open('', '_blank')
  try {
    const response = await proxy.$axios.get(`solicitudes-laboratorio/${row.id}/pdf`, { responseType: 'blob' })
    const archivo = new Blob([response.data], { type: 'application/pdf' })
    const urlArchivo = URL.createObjectURL(archivo)
    const enlace = document.createElement('a')
    enlace.href = urlArchivo
    enlace.download = `laboratorio_${row.codigo_solicitud}.pdf`
    enlace.click()

    const mensaje = [
      `Clínica URME - Laboratorio Clínico`,
      `Paciente: ${row.paciente?.nombre_completo || ''}`,
      `Informe: ${row.codigo_solicitud}`,
      `Adjuntamos su informe de laboratorio en formato PDF.`,
    ].join('\n')
    const whatsappUrl = `https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}`
    if (ventanaWhatsApp) ventanaWhatsApp.location.href = whatsappUrl
    else window.open(whatsappUrl, '_blank')

    setTimeout(() => URL.revokeObjectURL(urlArchivo), 60000)
    proxy.$alert.success('PDF descargado y chat de WhatsApp abierto')
  } catch (error) {
    ventanaWhatsApp?.close()
    proxy.$alert.error(error.response?.data?.message || 'No se pudo preparar el envío por WhatsApp')
  }
}
function normalizarTelefono (value) {
  let numero = String(value || '').replace(/\D/g, '')
  if (!numero || numero === '0') return null
  if (numero.startsWith('00')) numero = numero.slice(2)
  if (numero.length === 8) numero = `591${numero}`
  if (numero.startsWith('0') && numero.length === 9) numero = `591${numero.slice(1)}`
  return numero.length >= 11 && numero.length <= 15 ? numero : null
}
function eliminar (row) {
  proxy.$alert.dialog(`¿Eliminar la solicitud ${row.codigo_solicitud}?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`solicitudes-laboratorio/${row.id}`)
      proxy.$alert.success('Solicitud eliminada')
      recargar()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
function estadoColor (estado) {
  return { CREADO: 'grey-7', ATENDIENDO: 'orange', ENVIADO_ANALITICA: 'indigo', ANALIZADO: 'blue', FINALIZADO: 'positive' }[estado] || 'grey'
}

cargar()
</script>

<style scoped>
.sol-sub {
  font-size: 10px;
  line-height: 1.3;
}
.sol-datos {
  font-size: 11px;
  line-height: 1.4;
}
.sol-pruebas {
  max-width: 320px;
  white-space: normal;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 2px 8px;
}

.sol-lista :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.sol-lista :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.sol-lista :deep(.q-field--dense .q-field__native),
.sol-lista :deep(.q-field--dense .q-field__input),
.sol-lista :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
.sol-lista :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}
</style>
