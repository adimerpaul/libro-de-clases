<template>
  <q-page class="q-pa-sm cierres-caja">
    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver los cierres de caja</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center q-mb-xs">
        <div>
          <div class="text-h6 text-weight-bold">Cierres de caja</div>
          <div class="text-caption text-grey-6">Un cierre por usuario y por día</div>
        </div>
        <q-space />
        <q-btn flat round dense color="primary" icon="refresh" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <!-- Rangos rápidos: por defecto la semana en curso -->
      <div class="row q-col-gutter-xs q-mb-xs items-center">
        <div class="col-auto">
          <q-btn-toggle v-model="rango" dense unelevated no-caps toggle-color="primary"
                        color="grey-3" text-color="grey-8" :options="rangos"
                        @update:model-value="aplicarRango" />
        </div>
        <q-space />
        <!-- Sin 'Ver Montos Caja' solo se muestra lo declarado, nunca el
             total del sistema ni la diferencia. -->
        <div class="col-auto text-caption text-grey-7">
          <template v-if="canMontos">
            Sistema: <b>{{ money(totalSistema) }} Bs</b>
            <span class="q-mx-xs">·</span>
          </template>
          Declarado: <b class="text-primary">{{ money(totalDeclarado) }} Bs</b>
          <template v-if="canMontos">
            <span class="q-mx-xs">·</span>
            Diferencia:
            <b :class="diferencia(totalDiferencia).texto">
              <q-icon :name="diferencia(totalDiferencia).icono" size="13px" />
              {{ diferencia(totalDiferencia).monto }} Bs
              <q-tooltip>{{ diferencia(totalDiferencia).titulo }}</q-tooltip>
            </b>
          </template>
        </div>
      </div>

      <div class="row q-col-gutter-xs q-mb-xs items-center">
        <div class="col-auto">
          <q-input v-model="filtro.fecha_inicio" label="Desde" dense outlined type="date"
                   style="width:150px" @update:model-value="fechaManual" />
        </div>
        <div class="col-auto">
          <q-input v-model="filtro.fecha_fin" label="Hasta" dense outlined type="date"
                   style="width:150px" @update:model-value="fechaManual" />
        </div>
        <!-- El filtro por usuario solo aparece si el usuario puede listar usuarios. -->
        <div v-if="usuarios.length" class="col-auto">
          <q-select v-model="filtro.user_id" label="Usuario" dense outlined clearable
                    :options="usuarios" option-value="id" option-label="name"
                    emit-value map-options style="width:220px" @update:model-value="buscar" />
        </div>
        <div class="col-auto">
          <q-select v-model="porPagina" label="Por página" dense outlined
                    :options="[15, 25, 50, 100]" style="width:110px" @update:model-value="buscar" />
        </div>
      </div>

      <q-markup-table dense flat bordered separator="cell" class="full-width tabla-compacta">
        <thead>
          <tr class="bg-grey-1 text-grey-7 text-uppercase">
            <th class="text-center">Opciones</th>
            <th class="text-left">Fecha</th>
            <th class="text-left">Usuario</th>
            <th class="text-right">Ventas del día</th>
            <th v-if="canMontos" class="text-right">Sistema (Bs)</th>
            <th class="text-right">Declarado (Bs)</th>
            <th v-if="canMontos" class="text-right">Diferencia</th>
            <th class="text-left">Cerrado el</th>
            <th class="text-center">Corrección</th>
            <th class="text-left">Validación</th>
            <th class="text-left">Comentario</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="canMontos ? 11 : 9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
          </tr>
          <tr v-else-if="!cierres.length">
            <td :colspan="canMontos ? 11 : 9" class="text-center text-grey-5 q-pa-md">Sin cierres registrados</td>
          </tr>
          <tr v-else v-for="row in cierres" :key="row.id">
            <td class="text-center">
              <q-btn-dropdown label="Opciones" no-caps size="9px" dense rounded unelevated color="primary">
                <q-list dense>
                  <q-item v-if="canValidar && !row.validado" clickable v-close-popup @click="validar(row)">
                    <q-item-section avatar><q-icon name="verified" color="positive" size="18px" /></q-item-section>
                    <q-item-section>
                      <q-item-label class="text-positive">Validar cierre</q-item-label>
                      <q-item-label caption>Lo deja cerrado: ya no admite correcciones</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="verVentas(row)">
                    <q-item-section avatar><q-icon name="receipt_long" size="18px" /></q-item-section>
                    <q-item-section><q-item-label>Ver todas las ventas</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="imprimirVoucher(row)">
                    <q-item-section avatar><q-icon name="print" color="primary" size="18px" /></q-item-section>
                    <q-item-section>
                      <q-item-label>Imprimir voucher</q-item-label>
                      <q-item-label caption>Comprobante del cierre</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="exportarExcel(row)">
                    <q-item-section avatar><q-icon name="grid_on" color="green-8" size="18px" /></q-item-section>
                    <q-item-section><q-item-label>Exportar Excel</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="exportarPdf(row)">
                    <q-item-section avatar><q-icon name="picture_as_pdf" color="red-8" size="18px" /></q-item-section>
                    <q-item-section><q-item-label>Exportar PDF</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td>{{ formatSoloFecha(row.fecha) }}</td>
            <td>{{ row.user?.name || '—' }}</td>
            <td class="text-right">{{ row.cantidad_ventas }}</td>
            <td v-if="canMontos" class="text-right">{{ money(row.monto_sistema) }}</td>
            <td class="text-right text-weight-bold">{{ money(row.monto) }}</td>
            <td v-if="canMontos" class="text-right">
              <q-badge :color="diferencia(row.diferencia).color" class="text-weight-bold">
                <q-icon :name="diferencia(row.diferencia).icono" size="12px" class="q-mr-xs" />
                {{ diferencia(row.diferencia).monto }}
                <q-tooltip>{{ diferencia(row.diferencia).titulo }}</q-tooltip>
              </q-badge>
            </td>
            <td>{{ formatFecha(row.fecha_hora) }}</td>
            <td class="text-center">
              <q-badge v-if="row.modificado_en" color="orange-8">
                {{ formatFecha(row.modificado_en) }}
              </q-badge>
              <span v-else class="text-grey-5">—</span>
            </td>
            <td>
              <template v-if="row.validado">
                <q-badge color="positive" class="text-weight-bold">
                  <q-icon name="verified" size="12px" class="q-mr-xs" />VALIDADO
                </q-badge>
                <div class="text-grey-8">{{ row.validado_por?.name || '—' }}</div>
                <div class="text-grey-6">{{ formatFecha(row.validado_en) }}</div>
              </template>
              <q-badge v-else color="orange-1" text-color="orange-9" class="text-weight-bold">SIN VALIDAR</q-badge>
            </td>
            <td>{{ row.comentario || '—' }}</td>
          </tr>
        </tbody>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-xs q-px-xs">
        <div class="text-caption text-grey-6">
          Total: {{ total }} | Página {{ page }} de {{ paginas }}
        </div>
        <q-pagination v-model="page" :max="paginas" :max-pages="6"
                      boundary-links direction-links size="sm" @update:model-value="cargar" />
      </div>

      <!-- ── Todas las ventas del cierre ── -->
      <q-dialog v-model="dialogVentas" :maximized="$q.screen.lt.md">
        <q-card class="ventas-card">
          <q-card-section class="row items-center q-py-sm bg-primary text-white">
            <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Ventas del cierre</div>
              <div class="text-caption">
                {{ cierreSel?.user?.name || '—' }} · {{ formatSoloFecha(cierreSel?.fecha) }}
              </div>
            </div>
            <q-space />
            <q-btn flat dense no-caps color="white" icon="print" label="Voucher"
                   :loading="exportando === 'voucher'" @click="imprimirVoucher(cierreSel)" />
            <q-btn flat dense no-caps color="white" icon="grid_on" label="Excel" class="q-ml-xs"
                   :loading="exportando === 'excel'" @click="exportarExcel(cierreSel)" />
            <q-btn flat dense no-caps color="white" icon="picture_as_pdf" label="PDF" class="q-mx-xs"
                   :loading="exportando === 'pdf'" @click="exportarPdf(cierreSel)" />
            <q-btn icon="close" flat round dense color="white" v-close-popup />
          </q-card-section>

          <q-card-section class="q-pa-sm">
            <div class="row q-col-gutter-xs q-mb-xs items-center text-caption text-grey-8">
              <div v-if="canMontos" class="col-auto">Sistema: <b>{{ money(cierreSel?.monto_sistema) }} Bs</b></div>
              <div class="col-auto">{{ canMontos ? '· ' : '' }}Declarado: <b>{{ money(cierreSel?.monto) }} Bs</b></div>
              <div v-if="canMontos" class="col-auto">
                · Diferencia:
                <b :class="diferencia(cierreSel?.diferencia).texto">
                  <q-icon :name="diferencia(cierreSel?.diferencia).icono" size="13px" />
                  {{ diferencia(cierreSel?.diferencia).monto }} Bs
                  <q-tooltip>{{ diferencia(cierreSel?.diferencia).titulo }}</q-tooltip>
                </b>
              </div>
              <q-space />
              <div class="col-auto">
                <q-select v-model="porPaginaVentas" dense outlined label="Por página"
                          :options="[15, 25, 50, 100]" style="width:110px"
                          @update:model-value="cargarVentas(1)" />
              </div>
            </div>

            <q-markup-table dense flat bordered separator="cell" class="full-width tabla-compacta">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th class="text-left">N°</th>
                  <th class="text-left">Fecha y hora</th>
                  <th class="text-center">Tipo</th>
                  <th class="text-left">Cliente / Paciente</th>
                  <th class="text-left">Detalle</th>
                  <th class="text-center">Estado</th>
                  <th class="text-left">Pago</th>
                  <th class="text-right">Ítems</th>
                  <th class="text-right">Total (Bs)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingVentas">
                  <td colspan="9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
                </tr>
                <tr v-else-if="!ventas.length">
                  <td colspan="9" class="text-center text-grey-5 q-pa-md">Este cierre no tiene movimientos</td>
                </tr>
                <tr v-else v-for="v in ventas" :key="v.id">
                  <td>{{ v.id }}</td>
                  <td>{{ formatFecha(v.fecha_hora_cobro || v.fecha_hora) }}</td>
                  <td class="text-center">
                    <q-badge rounded
                             :color="esEgreso(v) ? 'deep-orange-1' : 'blue-1'"
                             :text-color="esEgreso(v) ? 'deep-orange-9' : 'blue-9'"
                             class="text-weight-bold">{{ esEgreso(v) ? 'GASTO' : 'VENTA' }}</q-badge>
                  </td>
                  <td>{{ v.paciente?.nombre_completo || v.cliente || (esEgreso(v) ? 'GASTO DE CAJA' : 'SIN CLIENTE') }}</td>
                  <td class="text-grey-7">{{ (v.detalles || []).map(d => d.nombre).join(', ') || '—' }}</td>
                  <td class="text-center">
                    <q-badge :color="v.estado === 'ACTIVO' ? 'positive'
                      : (v.estado === 'PENDIENTE' ? 'orange-8' : 'negative')">
                      {{ v.estado }}
                    </q-badge>
                  </td>
                  <td>{{ v.tipo_pago || '—' }}</td>
                  <td class="text-right">{{ (v.detalles || []).length }}</td>
                  <!-- El gasto se muestra en negativo: es dinero que salió de caja -->
                  <td class="text-right text-weight-bold" :class="{ 'text-deep-orange-8': esEgreso(v) }">
                    {{ esEgreso(v) ? '-' : '' }}{{ money(v.total) }}
                  </td>
                </tr>
              </tbody>
            </q-markup-table>

            <div class="row items-center justify-between q-mt-xs q-px-xs">
              <div class="text-caption text-grey-6">
                Total: {{ totalVentas }} | Página {{ pageVentas }} de {{ paginasVentas }}
              </div>
              <q-pagination v-model="pageVentas" :max="paginasVentas" :max-pages="6"
                            boundary-links direction-links size="sm" @update:model-value="cargarVentas()" />
            </div>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { formatBoliviaDate, formatBoliviaDateTime } from '../../../addons/dateTime'
import { imprimirCierreCaja } from '../../../addons/cierreCajaPrint'

const { proxy } = getCurrentInstance()

const canVer = computed(() => proxy.$store.hasPermission('Ver Cierres Caja'))
// Sin 'Ver Montos Caja' se ve solo el efectivo declarado, no el sistema ni la diferencia.
const canMontos = computed(() => proxy.$store.hasPermission('Ver Montos Caja'))
const canValidar = computed(() => proxy.$store.hasPermission('Validar Cierres Caja'))

const cierres = ref([])
const usuarios = ref([])
const loading = ref(false)
const page = ref(1)
const total = ref(0)
const porPagina = ref(15)
const filtro = ref({ fecha_inicio: '', fecha_fin: '', user_id: null })

// ── Rango de fechas: la semana en curso es el filtro por defecto ──
const rangos = [
  { label: 'Esta semana', value: 'semana' },
  { label: 'Semana pasada', value: 'semana_pasada' },
  { label: 'Hoy', value: 'hoy' },
  { label: 'Este mes', value: 'mes' },
  { label: 'Todo', value: 'todo' },
]
const rango = ref('semana')

function ymd (fecha) {
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return fecha.getFullYear() + '-' + mes + '-' + dia
}

/** Lunes de la semana a la que pertenece la fecha (semana de lunes a domingo). */
function lunesDe (fecha) {
  const base = new Date(fecha)
  base.setDate(base.getDate() - ((base.getDay() + 6) % 7))
  return base
}

function sumarDias (fecha, dias) {
  const base = new Date(fecha)
  base.setDate(base.getDate() + dias)
  return base
}

function aplicarRango (valor) {
  const hoy = new Date()
  const opcion = valor || rango.value

  if (opcion === 'hoy') {
    filtro.value.fecha_inicio = ymd(hoy)
    filtro.value.fecha_fin = ymd(hoy)
  } else if (opcion === 'semana') {
    const lunes = lunesDe(hoy)
    filtro.value.fecha_inicio = ymd(lunes)
    filtro.value.fecha_fin = ymd(sumarDias(lunes, 6))
  } else if (opcion === 'semana_pasada') {
    const lunes = sumarDias(lunesDe(hoy), -7)
    filtro.value.fecha_inicio = ymd(lunes)
    filtro.value.fecha_fin = ymd(sumarDias(lunes, 6))
  } else if (opcion === 'mes') {
    filtro.value.fecha_inicio = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 1))
    filtro.value.fecha_fin = ymd(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0))
  } else {
    filtro.value.fecha_inicio = ''
    filtro.value.fecha_fin = ''
  }

  buscar()
}

/** Tocar las fechas a mano rompe el rango rápido: pasa a ser un rango libre. */
function fechaManual () {
  rango.value = null
  buscar()
}

const paginas = computed(() => Math.max(1, Math.ceil(total.value / porPagina.value)))
const totalDeclarado = computed(() => cierres.value.reduce((suma, c) => suma + Number(c.monto || 0), 0))
const totalSistema = computed(() => cierres.value.reduce((suma, c) => suma + Number(c.monto_sistema || 0), 0))
const totalDiferencia = computed(() => Math.round((totalDeclarado.value - totalSistema.value) * 100) / 100)

function money (v) { return Number(v || 0).toFixed(2) }

/**
 * La diferencia se lee por el signo: sobrante en verde con +, faltante en rojo
 * con −, y caja cuadrada en gris. El monto va en valor absoluto porque el
 * signo ya lo dice el ícono.
 */
function diferencia (valor) {
  const n = Math.round((Number(valor) || 0) * 100) / 100
  if (n > 0) return { icono: 'add', color: 'positive', texto: 'text-positive', titulo: 'Sobrante: se declaró de más', monto: money(n) }
  if (n < 0) return { icono: 'remove', color: 'negative', texto: 'text-negative', titulo: 'Faltante: se declaró de menos', monto: money(-n) }
  return { icono: 'check', color: 'grey-6', texto: 'text-grey-7', titulo: 'Sin diferencia: la caja cuadra', monto: money(0) }
}

function formatFecha (v) { return formatBoliviaDateTime(v) }
function formatSoloFecha (v) { return formatBoliviaDate(v, '—') }

function buscar () {
  page.value = 1
  cargar()
}

async function cargar () {
  if (!canVer.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('cierres-caja', {
      params: {
        fecha_inicio: filtro.value.fecha_inicio || undefined,
        fecha_fin: filtro.value.fecha_fin || undefined,
        user_id: filtro.value.user_id || undefined,
        page: page.value,
        per_page: porPagina.value,
      },
    })
    cierres.value = data.data || []
    total.value = data.total || 0
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los cierres')
  } finally {
    loading.value = false
  }
}

async function cargarUsuarios () {
  try {
    const { data } = await proxy.$axios.get('users')
    usuarios.value = data.data || data || []
  } catch {
    // El filtro por usuario es opcional: sin la lista la pantalla sigue funcionando.
  }
}

// ── Validación ────────────────────────────────────────────────────
function validar (row) {
  proxy.$alert.confirm(
    '¿Validar el cierre de ' + (row.user?.name || '—') + ' del ' + formatSoloFecha(row.fecha) +
    '? Una vez validado queda cerrado y ya no se puede corregir.',
  ).onOk(async () => {
    try {
      const { data } = await proxy.$axios.put('cierres-caja/' + row.id + '/validar')
      proxy.$alert.success(data.message || 'Cierre validado')
      cargar()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo validar el cierre')
    }
  })
}

// ── Ventas de un cierre ───────────────────────────────────────────
const dialogVentas = ref(false)
const cierreSel = ref(null)
const ventas = ref([])

// Un movimiento de caja puede ser una venta (INGRESO) o un gasto (EGRESO).
function esEgreso (v) { return v?.tipo_movimiento === 'EGRESO' }
const loadingVentas = ref(false)
const pageVentas = ref(1)
const totalVentas = ref(0)
const porPaginaVentas = ref(15)
const paginasVentas = computed(() => Math.max(1, Math.ceil(totalVentas.value / porPaginaVentas.value)))

function verVentas (row) {
  cierreSel.value = row
  dialogVentas.value = true
  cargarVentas(1)
}

async function cargarVentas (pagina) {
  if (!cierreSel.value) return
  if (pagina) pageVentas.value = pagina
  loadingVentas.value = true
  try {
    const { data } = await proxy.$axios.get('cierres-caja/' + cierreSel.value.id + '/ventas', {
      params: { page: pageVentas.value, per_page: porPaginaVentas.value },
    })
    cierreSel.value = data.cierre || cierreSel.value
    ventas.value = data.ventas?.data || []
    totalVentas.value = data.ventas?.total || 0
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las ventas del cierre')
  } finally {
    loadingVentas.value = false
  }
}

// ── Exportaciones ─────────────────────────────────────────────────
const exportando = ref(null)

/**
 * Voucher del cierre en media carta. Se piden las ventas del cierre (hasta las
 * 100 que admite el endpoint) para imprimir el detalle y el corte por forma de
 * pago; si el cierre tiene más, el voucher sale con el resumen y avisa que el
 * detalle completo está en el PDF.
 */
async function imprimirVoucher (cierre) {
  if (!cierre) return
  exportando.value = 'voucher'
  try {
    const { data } = await proxy.$axios.get('cierres-caja/' + cierre.id + '/ventas', {
      params: { page: 1, per_page: 100 },
    })
    imprimirCierreCaja(data.cierre || cierre, data.ventas?.data || [], {
      totalVentas: data.ventas?.total ?? null,
      verMontos: canMontos.value,
    })
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo imprimir el voucher del cierre')
  } finally {
    exportando.value = null
  }
}

function nombreArchivo (cierre, extension) {
  return 'cierre_caja_' + String(cierre.fecha || '').slice(0, 10) + '_' + cierre.id + '.' + extension
}

async function exportarExcel (cierre) {
  if (!cierre) return
  exportando.value = 'excel'
  try {
    const res = await proxy.$axios.get('cierres-caja/' + cierre.id + '/ventas/export-excel', {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = nombreArchivo(cierre, 'xlsx')
    a.click()
    window.URL.revokeObjectURL(url)
  } catch {
    proxy.$alert.error('Error al generar el Excel')
  } finally {
    exportando.value = null
  }
}

async function exportarPdf (cierre) {
  if (!cierre) return
  exportando.value = 'pdf'
  try {
    const res = await proxy.$axios.get('cierres-caja/' + cierre.id + '/ventas/export-pdf', {
      responseType: 'blob',
    })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch {
    proxy.$alert.error('Error al generar el PDF')
  } finally {
    exportando.value = null
  }
}

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) {
    aplicarRango('semana')
    cargarUsuarios()
  }
}, { immediate: true })
</script>

<style scoped>
.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}

.ventas-card {
  width: min(96vw, 1100px);
  max-width: 1100px;
}

.cierres-caja :deep(.q-field--dense .q-field__control),
.cierres-caja :deep(.q-field--dense .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.cierres-caja :deep(.q-field--dense .q-field__native),
.cierres-caja :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
</style>
