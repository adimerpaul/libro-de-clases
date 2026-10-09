<template>
  <q-page class="q-pa-sm dashboard">

    <!-- Sin permiso de panel: solo la bienvenida y el acceso a ventas -->
    <div v-if="!canDashboard" class="column items-center justify-center q-gutter-md"
         style="min-height:78vh">
      <img src="/logo.png" alt="Clínica URME" class="bienvenida__logo" />
      <div class="text-h6 text-weight-bold text-center">Clínica URME</div>
      <div class="text-body2 text-grey-6 text-center">
        Bienvenido, {{ proxy.$store.user.name || proxy.$store.user.username }}
      </div>
      <q-btn unelevated no-caps rounded size="lg" color="primary" icon="point_of_sale"
             label="Ir a ventas" to="/ventas" />
    </div>

    <template v-else>

    <!-- ── Encabezado + filtros ──────────────────────────────────── -->
    <div class="row items-center q-col-gutter-xs q-mb-xs">
      <div class="col-auto">
        <div class="text-subtitle1 text-weight-bold">Panel general</div>
      </div>

      <div class="col-auto">
        <q-btn-toggle
          v-model="periodo"
          dense unelevated no-caps
          toggle-color="primary"
          color="white"
          text-color="grey-7"
          class="dashboard__toggle"
          :options="[
            { label: 'Ayer', value: 'ayer' },
            { label: 'Hoy', value: 'hoy' },
            { label: 'Semana', value: 'semana' },
            { label: 'Mes', value: 'mes' },
            { label: 'Año', value: 'anio' },
            { label: 'Entre fechas', value: 'rango' }
          ]"
          @update:model-value="onPeriodo"
        />
      </div>

      <!-- Entre fechas -->
      <template v-if="periodo === 'rango'">
        <div class="col-auto">
          <q-input v-model="desde" dense outlined bg-color="white" mask="####-##-##"
                   class="dashboard__fecha" label="Desde" @update:model-value="cargarConRango">
            <template #append>
              <q-icon name="event" size="18px" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="desde" mask="YYYY-MM-DD" minimal @update:model-value="cargarConRango" />
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-input v-model="hasta" dense outlined bg-color="white" mask="####-##-##"
                   class="dashboard__fecha" label="Hasta" @update:model-value="cargarConRango">
            <template #append>
              <q-icon name="event" size="18px" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="hasta" mask="YYYY-MM-DD" minimal @update:model-value="cargarConRango" />
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>
      </template>

      <div class="col-auto">
        <q-btn-toggle
          v-model="tipoGrafico"
          dense unelevated no-caps
          toggle-color="primary"
          color="white"
          text-color="grey-7"
          class="dashboard__toggle"
          :options="[
            { value: 'montanas', icon: 'show_chart', slot: 'montanas' },
            { value: 'histograma', icon: 'bar_chart', slot: 'histograma' }
          ]"
        >
          <template #montanas><q-tooltip>Montañas</q-tooltip></template>
          <template #histograma><q-tooltip>Histograma</q-tooltip></template>
        </q-btn-toggle>
      </div>

      <div class="col-auto">
        <q-btn flat dense round icon="refresh" color="grey-7" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <q-space />

      <div class="col-auto">
        <q-btn unelevated no-caps dense rounded color="primary" icon="point_of_sale"
               label="Ir a ventas" to="/ventas" class="q-px-md" />
      </div>
    </div>

    <!-- ── Resumen del período en una sola línea ─────────────────── -->
    <div class="resumen">
      <span class="resumen__rango">
        {{ fechaCorta(data.rango.desde) }} — {{ fechaCorta(data.rango.hasta) }}
      </span>
      <span v-for="dato in resumenLinea" :key="dato.label" class="resumen__dato"
            :class="dato.alerta ? 'resumen__dato--alerta' : ''">
        <span class="resumen__label">{{ dato.label }}</span>
        <span class="resumen__value">{{ dato.value }}</span>
      </span>
    </div>

    <!-- Sin módulos habilitados -->
    <div v-if="!loading && !hayModulos" class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="insights" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin indicadores disponibles</div>
      <div class="text-body2 text-grey-6">Su usuario aún no tiene permisos sobre los módulos del panel</div>
    </div>

    <!-- ── Movimiento del período ────────────────────────────────── -->
    <div v-if="permisos.ventas || permisos.compras" class="row q-col-gutter-xs q-mb-xs">
      <div class="col-12">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Movimiento {{ tituloGranularidad }}</div>
              <div class="panel__sub">Ingresos por ventas y egresos por compras</div>
            </div>
            <div class="panel__total">
              {{ money(totalSerieVentas) }} <span class="panel__unit">Bs vendidos</span>
            </div>
          </div>
          <q-inner-loading :showing="loading" />
          <apexchart v-if="!loading" :key="tipoGrafico" :type="tipoGrafico === 'montanas' ? 'area' : 'bar'"
                     height="220" :options="opcionesMovimiento" :series="serieMovimiento" />
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-xs q-mb-xs">
      <!-- Forma de pago (torta) -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Forma de pago</div>
              <div class="panel__sub">Efectivo, QR y demás medios</div>
            </div>
          </div>
          <apexchart v-if="data.tipo_pago.length" type="pie" height="230"
                     :options="opcionesTipoPago" :series="data.tipo_pago.map(f => f.total)" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>

      <!-- Vendedores -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Quién vendió más</div>
              <div class="panel__sub">
                <template v-if="lider(data.top_vendedores)">
                  {{ lider(data.top_vendedores).nombre }} · {{ money(lider(data.top_vendedores).total) }} Bs
                </template>
                <template v-else>Monto atendido por cada usuario</template>
              </div>
            </div>
          </div>
          <apexchart v-if="data.top_vendedores.length" type="bar" height="230"
                     :options="opcionesVendedores" :series="serieVendedores" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>

      <!-- Profesionales -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Profesional que más deriva</div>
              <div class="panel__sub">
                <template v-if="lider(data.top_profesionales)">
                  {{ lider(data.top_profesionales).nombre }} · {{ money(lider(data.top_profesionales).total) }} Bs
                </template>
                <template v-else>Ventas asignadas a cada doctor</template>
              </div>
            </div>
          </div>
          <apexchart v-if="data.top_profesionales.length" type="bar" height="230"
                     :options="opcionesProfesionales" :series="serieProfesionales" />
          <div v-else class="vacio">Sin ventas con profesional asignado</div>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-xs q-mb-xs">
      <!-- Top productos -->
      <div v-if="permisos.ventas" class="col-12 col-md-5">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Producto más consumido</div>
              <div class="panel__sub">
                <template v-if="lider(data.top_productos)">
                  {{ lider(data.top_productos).nombre }} · {{ Number(lider(data.top_productos).cantidad) }} unidad(es)
                </template>
                <template v-else>Productos y servicios más vendidos</template>
              </div>
            </div>
          </div>
          <apexchart v-if="data.top_productos.length" type="bar"
                     :height="Math.max(230, data.top_productos.length * 28 + 40)"
                     :options="opcionesTopProductos" :series="serieTopProductos" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>

      <!-- Ventas por categoría -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Ventas por categoría</div>
              <div class="panel__sub">Farmacia, servicios y estudios</div>
            </div>
          </div>
          <apexchart v-if="data.por_tipo_producto.length" type="pie" height="230"
                     :options="opcionesTipoProducto" :series="data.por_tipo_producto.map(f => f.total)" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>

      <!-- Solicitudes de laboratorio -->
      <div v-if="permisos.laboratorio" class="col-12 col-md-3">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Laboratorio</div>
              <div class="panel__sub">Solicitudes por estado</div>
            </div>
          </div>
          <apexchart v-if="data.solicitudes_estado.length" type="bar" height="230"
                     :options="opcionesSolicitudes" :series="serieSolicitudes" />
          <div v-else class="vacio">Sin solicitudes en el período</div>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-xs">
      <!-- Actividad por hora -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Actividad por hora</div>
              <div class="panel__sub">Cantidad de ventas según hora de atención</div>
            </div>
          </div>
          <apexchart type="bar" height="210" :options="opcionesHora" :series="serieHora" />
        </q-card>
      </div>

      <!-- Vencimientos -->
      <div v-if="permisos.productos" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Lotes por vencer</div>
              <div class="panel__sub">Lotes con existencia, agrupados por urgencia</div>
            </div>
          </div>
          <apexchart type="bar" height="210" :options="opcionesVencimientos" :series="serieVencimientos" />
        </q-card>
      </div>

      <!-- Stock crítico -->
      <div v-if="permisos.productos" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Stock crítico</div>
              <div class="panel__sub">Productos con 10 unidades o menos</div>
            </div>
          </div>
          <q-list separator dense class="q-mt-xs stock">
            <q-item v-for="p in data.stock_critico" :key="p.producto_id" dense>
              <q-item-section>
                <q-item-label class="text-caption">{{ p.nombre }}</q-item-label>
                <q-item-label caption>{{ p.codigo }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge :color="p.existencia <= 3 ? 'negative' : 'orange-8'" class="text-weight-bold">
                  {{ Number(p.existencia) }}
                </q-badge>
              </q-item-section>
            </q-item>
            <q-item v-if="!data.stock_critico.length">
              <q-item-section class="text-grey-6 text-caption">
                Ningún producto en nivel crítico
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>
    </div>

    </template>

  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

/* ── Paleta validada (categórica, orden fijo — nunca ciclada) ──────
   Verificada con el validador de paleta sobre superficie #ffffff.
   SERIES se usa en gráficos donde solo importan los pares contiguos
   (líneas, barras). TORTA es el subconjunto que además supera la
   validación con todos los pares, porque una torta se ordena por tamaño
   y cualquier porción puede terminar junto a cualquier otra. */
const SERIES = ['#1baf7a', '#eb6834', '#2a78d6', '#eda100', '#e87ba4', '#4a3aa7', '#008300', '#e34948']
const TORTA = ['#1baf7a', '#eb6834', '#2a78d6', '#4a3aa7']
const ESTADO = { bueno: '#0ca30c', alerta: '#fab219', serio: '#ec835a', critico: '#d03b3b' }
const TINTA = { primaria: '#0b0b0b', secundaria: '#52514e', tenue: '#898781', grilla: '#e1e0d9', eje: '#c3c2b7' }
const FUENTE = 'system-ui, -apple-system, "Segoe UI", sans-serif'

const loading = ref(false)
const periodo = ref('semana')
const tipoGrafico = ref('montanas')
const desde = ref(isoHoy())
const hasta = ref(isoHoy())

const data = ref({
  rango: { periodo: 'semana', granularidad: 'dia', dias: 0, desde: '', hasta: '' },
  permisos: {},
  resumen: {},
  serie: [],
  tipo_pago: [],
  top_productos: [],
  por_tipo_producto: [],
  top_vendedores: [],
  top_profesionales: [],
  ventas_por_hora: [],
  solicitudes_estado: [],
  vencimientos: [],
  stock_critico: [],
})

// Sin 'Ver Dashboard' la página sigue siendo accesible, pero solo muestra
// la bienvenida con el acceso a ventas — no se consulta el endpoint.
const canDashboard = computed(() => proxy.$store.hasPermission('Ver Dashboard'))

const resumen = computed(() => data.value.resumen || {})
const permisos = computed(() => data.value.permisos || {})
const hayModulos = computed(() => Object.values(permisos.value).some(Boolean))

const tituloGranularidad = computed(() => ({
  hora: 'por hora',
  mes: 'por mes',
}[data.value.rango.granularidad] || 'por día'))

async function cargar () {
  loading.value = true
  try {
    const params = { periodo: periodo.value }
    if (periodo.value === 'rango') {
      params.desde = desde.value
      params.hasta = hasta.value
    }
    const res = await proxy.$axios.get('dashboard', { params })
    data.value = res.data
  } catch (e) {
    proxy.$alert.error('No se pudo cargar el panel: ' + (e.response?.data?.message || e.message))
  } finally {
    loading.value = false
  }
}

function onPeriodo (valor) {
  // Al abrir "entre fechas" se parte del rango que ya se estaba viendo,
  // así el usuario ajusta un extremo en lugar de escribir ambos.
  if (valor === 'rango') {
    desde.value = data.value.rango.desde || isoHoy()
    hasta.value = data.value.rango.hasta || isoHoy()
  }
  cargar()
}

function cargarConRango () {
  if (esFecha(desde.value) && esFecha(hasta.value)) cargar()
}

function esFecha (v) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(v || ''))
}

function isoHoy () {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// Los permisos pueden llegar después del montaje (respuesta de /me),
// por eso se espera a que estén resueltos antes de pedir los datos.
let cargado = false
watch(canDashboard, (puede) => {
  if (puede && !cargado) { cargado = true; cargar() }
}, { immediate: true })

/* ── Formatos ─────────────────────────────────────────────────── */
function money (v) {
  return Number(v || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function moneyCorto (v) {
  const n = Number(v || 0)
  if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(1) + ' M'
  if (Math.abs(n) >= 1000) return (n / 1000).toFixed(1) + ' k'
  return n.toFixed(0)
}
function fechaCorta (iso) {
  if (!iso) return ''
  const [a, m, d] = iso.split('-')
  return `${d}/${m}/${a}`
}
function lider (filas) {
  return filas && filas.length ? filas[0] : null
}

/* El color sigue a la entidad, nunca a su posición. Cada grupo mantiene un
   registro de ranura por clave: la primera carga lo siembra en orden
   alfabético y las claves nuevas se agregan al final, de modo que cambiar
   el rango de fechas jamás repinta las categorías que ya estaban. */
const registrosColor = new Map()

function coloresEstables (grupo, claves, paleta = TORTA) {
  if (!registrosColor.has(grupo)) registrosColor.set(grupo, new Map())
  const registro = registrosColor.get(grupo)

  const visibles = [...new Set(claves)]
  const entidades = visibles.filter(c => c !== 'OTROS').sort()
  for (const clave of entidades) {
    if (!registro.has(clave)) registro.set(clave, registro.size)
  }

  /* Cada clave conserva su ranura mientras esté libre; si el registro creció
     más que la paleta se toma la primera ranura disponible, para que dos
     categorías en pantalla nunca compartan color. OTROS ocupa siempre la
     última ranura: es un residuo, no una entidad. */
  const asignado = new Map()
  const usadas = new Set()
  if (visibles.includes('OTROS')) {
    asignado.set('OTROS', paleta.length - 1)
    usadas.add(paleta.length - 1)
  }
  for (const clave of entidades) {
    const preferida = registro.get(clave) % paleta.length
    let ranura = usadas.has(preferida) ? paleta.findIndex((_, i) => !usadas.has(i)) : preferida
    if (ranura < 0) ranura = preferida
    asignado.set(clave, ranura)
    usadas.add(ranura)
  }

  return claves.map(c => paleta[asignado.get(c)])
}

/* ── Resumen en línea (reemplaza las tarjetas) ────────────────── */
const resumenLinea = computed(() => {
  const r = resumen.value
  const p = permisos.value
  const lista = []
  if (p.ventas) {
    lista.push({ label: 'Vendido', value: money(r.ventas_total) + ' Bs' })
    lista.push({ label: 'Ventas', value: r.ventas_cantidad ?? 0 })
    lista.push({ label: 'Ticket', value: money(r.ticket_promedio) + ' Bs' })
    lista.push({ label: 'Por cobrar', value: money(r.ventas_pendientes) + ' Bs', alerta: Number(r.ventas_pendientes) > 0 })
  }
  if (p.compras) lista.push({ label: 'Compras', value: money(r.compras_total) + ' Bs' })
  if (p.pacientes) lista.push({ label: 'Pacientes nuevos', value: r.pacientes_nuevos ?? 0 })
  if (p.laboratorio) lista.push({ label: 'Lab. pendientes', value: r.solicitudes_pendientes ?? 0 })
  if (p.productos) {
    lista.push({ label: 'Por vencer', value: r.por_vencer ?? 0, alerta: Number(r.por_vencer) > 0 })
    lista.push({ label: 'Vencidos', value: r.vencidos ?? 0, alerta: Number(r.vencidos) > 0 })
  }
  return lista
})

/* ── Base común de los gráficos ───────────────────────────────── */
const base = {
  chart: {
    fontFamily: FUENTE,
    toolbar: { show: false },
    zoom: { enabled: false },
    animations: { easing: 'easeout', speed: 350 },
  },
  grid: {
    borderColor: TINTA.grilla,
    strokeDashArray: 0,
    padding: { left: 8, right: 12, top: 0 },
    xaxis: { lines: { show: false } },
  },
  dataLabels: { enabled: false },
  legend: {
    position: 'bottom',
    horizontalAlign: 'left',
    fontSize: '11px',
    fontFamily: FUENTE,
    labels: { colors: TINTA.secundaria },
    markers: { size: 5, offsetX: -3 },
    itemMargin: { horizontal: 8, vertical: 1 },
  },
  tooltip: { style: { fontFamily: FUENTE, fontSize: '12px' } },
  states: { active: { filter: { type: 'none' } } },
}

const ejeY = {
  labels: { style: { colors: TINTA.tenue, fontSize: '11px', fontFamily: FUENTE }, formatter: v => moneyCorto(v) },
  axisBorder: { show: false },
  axisTicks: { show: false },
}
const ejeX = {
  labels: { style: { colors: TINTA.tenue, fontSize: '11px', fontFamily: FUENTE } },
  axisBorder: { color: TINTA.eje },
  axisTicks: { show: false },
  crosshairs: { stroke: { color: TINTA.eje, width: 1, dashArray: 3 } },
}

/* ── 1. Movimiento del período (montañas o histograma) ────────── */
const totalSerieVentas = computed(() =>
  data.value.serie.reduce((suma, f) => suma + Number(f.ventas || 0), 0))

const serieMovimiento = computed(() => {
  const series = []
  if (permisos.value.ventas) {
    series.push({ name: 'Ventas', data: data.value.serie.map(f => Number(f.ventas)) })
  }
  if (permisos.value.compras) {
    series.push({ name: 'Compras', data: data.value.serie.map(f => Number(f.compras)) })
  }
  return series
})

const opcionesMovimiento = computed(() => {
  const montanas = tipoGrafico.value === 'montanas'
  return {
    ...base,
    chart: { ...base.chart, type: montanas ? 'area' : 'bar' },
    colors: [SERIES[0], SERIES[1]],
    stroke: montanas
      ? { width: 2, curve: 'straight' }
      : { show: true, width: 2, colors: ['transparent'] },
    fill: montanas
      ? { type: 'gradient', gradient: { shadeIntensity: 0, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 100] } }
      : { type: 'solid' },
    plotOptions: { bar: { columnWidth: '62%', borderRadius: 3, borderRadiusApplication: 'end' } },
    markers: { size: 0, strokeWidth: 2, strokeColors: '#ffffff', hover: { size: 5 } },
    legend: { ...base.legend, show: serieMovimiento.value.length > 1 },
    xaxis: {
      ...ejeX,
      categories: data.value.serie.map(f => f.etiqueta),
      tickAmount: Math.min(12, data.value.serie.length),
    },
    yaxis: ejeY,
    tooltip: {
      ...base.tooltip,
      shared: true,
      intersect: false,
      x: { formatter: (_v, { dataPointIndex }) => data.value.serie[dataPointIndex]?.detalle || '' },
      y: { formatter: v => money(v) + ' Bs' },
    },
  }
})

/* ── 2. Forma de pago (torta) ─────────────────────────────────── */
const torta = {
  ...base,
  chart: { ...base.chart, type: 'pie' },
  stroke: { width: 2, colors: ['#ffffff'] },
  dataLabels: {
    enabled: true,
    style: { fontSize: '11px', fontFamily: FUENTE, fontWeight: 600, colors: ['#ffffff'] },
    dropShadow: { enabled: false },
    formatter: v => v.toFixed(0) + '%',
  },
  legend: { ...base.legend, horizontalAlign: 'center' },
  tooltip: { ...base.tooltip, y: { formatter: v => money(v) + ' Bs' } },
}

const opcionesTipoPago = computed(() => ({
  ...torta,
  labels: data.value.tipo_pago.map(f => f.tipo_pago),
  colors: coloresEstables('tipo_pago', data.value.tipo_pago.map(f => f.tipo_pago)),
}))

/* ── 3. Ventas por categoría (torta) ──────────────────────────── */
const opcionesTipoProducto = computed(() => ({
  ...torta,
  labels: data.value.por_tipo_producto.map(f => f.tipo),
  colors: coloresEstables('tipo_producto', data.value.por_tipo_producto.map(f => f.tipo)),
}))

/* ── 4. Producto más consumido (barras horizontales) ──────────── */
const serieTopProductos = computed(() => ([
  { name: 'Vendido', data: data.value.top_productos.map(f => Number(f.total)) },
]))

const opcionesTopProductos = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[0]],
  plotOptions: {
    bar: { horizontal: true, barHeight: '58%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    offsetX: 6,
    style: { fontSize: '11px', fontFamily: FUENTE, fontWeight: 600, colors: [TINTA.secundaria] },
    formatter: v => money(v) + ' Bs',
  },
  grid: { ...base.grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
  xaxis: {
    ...ejeX,
    categories: data.value.top_productos.map(f => f.nombre),
    labels: { ...ejeX.labels, formatter: v => moneyCorto(v) },
  },
  yaxis: {
    labels: {
      style: { colors: TINTA.secundaria, fontSize: '11px', fontFamily: FUENTE },
      maxWidth: 180,
      formatter: v => (String(v).length > 30 ? String(v).slice(0, 29) + '…' : v),
    },
  },
  legend: { show: false },
  tooltip: {
    ...base.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) => {
        const fila = data.value.top_productos[dataPointIndex]
        return `${money(v)} Bs · ${Number(fila?.cantidad || 0)} unidad(es)`
      },
    },
  },
}))

/* ── 5. Quién vendió más (barras horizontales) ────────────────── */
const serieVendedores = computed(() => ([
  { name: 'Vendido', data: data.value.top_vendedores.map(f => Number(f.total)) },
]))

/* Ranking de personas: el primer puesto se pinta y el resto queda en gris,
   para que el líder se lea sin buscar la barra más larga. */
function opcionesRanking (filas, color) {
  return {
    ...opcionesTopProductos.value,
    colors: [color],
    plotOptions: {
      bar: {
        horizontal: true,
        barHeight: filas.length < 3 ? '34%' : '58%',
        borderRadius: 4,
        borderRadiusApplication: 'end',
        distributed: true,
      },
    },
    xaxis: {
      ...ejeX,
      categories: filas.map(f => f.nombre),
      labels: { ...ejeX.labels, formatter: v => moneyCorto(v) },
    },
    yaxis: {
      labels: {
        style: { colors: TINTA.secundaria, fontSize: '11px', fontFamily: FUENTE },
        maxWidth: 120,
        formatter: v => (String(v).length > 18 ? String(v).slice(0, 17) + '…' : v),
      },
    },
    legend: { show: false },
    tooltip: {
      ...base.tooltip,
      y: {
        formatter: (v, { dataPointIndex }) =>
          `${money(v)} Bs · ${filas[dataPointIndex]?.cantidad || 0} venta(s)`,
      },
    },
  }
}

const opcionesVendedores = computed(() => {
  const filas = data.value.top_vendedores
  return {
    ...opcionesRanking(filas, SERIES[2]),
    colors: filas.map((_, i) => (i === 0 ? SERIES[2] : '#c9d5e4')),
  }
})

/* ── 6. Profesional que más deriva (barras horizontales) ──────── */
const serieProfesionales = computed(() => ([
  { name: 'Derivado', data: data.value.top_profesionales.map(f => Number(f.total)) },
]))

const opcionesProfesionales = computed(() => {
  const filas = data.value.top_profesionales
  return {
    ...opcionesRanking(filas, SERIES[5]),
    colors: filas.map((_, i) => (i === 0 ? SERIES[5] : '#cfc9e6')),
  }
})

/* ── 7. Actividad por hora (barras) ───────────────────────────── */
const serieHora = computed(() => ([
  { name: 'Ventas', data: data.value.ventas_por_hora.map(f => f.cantidad) },
]))

const opcionesHora = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[2]],
  plotOptions: { bar: { columnWidth: '62%', borderRadius: 3, borderRadiusApplication: 'end' } },
  xaxis: {
    ...ejeX,
    categories: data.value.ventas_por_hora.map(f => f.hora),
    tickAmount: 8,
  },
  yaxis: {
    ...ejeY,
    labels: { ...ejeY.labels, formatter: v => Number(v).toFixed(0) },
  },
  legend: { show: false },
  tooltip: {
    ...base.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) =>
        `${v} venta(s) · ${money(data.value.ventas_por_hora[dataPointIndex]?.total)} Bs`,
    },
  },
}))

/* ── 8. Solicitudes por estado (barras) ───────────────────────────
   Los estados son etapas de un mismo flujo, no categorías rivales: una
   sola serie con las etapas nombradas en el eje lo dice mejor que una torta. */
const serieSolicitudes = computed(() => ([
  { name: 'Solicitudes', data: data.value.solicitudes_estado.map(f => f.cantidad) },
]))

const opcionesSolicitudes = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[2]],
  plotOptions: {
    bar: { horizontal: true, barHeight: '52%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    offsetX: 6,
    style: { fontSize: '11px', fontFamily: FUENTE, fontWeight: 700, colors: [TINTA.secundaria] },
  },
  grid: { ...base.grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
  xaxis: {
    ...ejeX,
    categories: data.value.solicitudes_estado.map(f => f.estado.replaceAll('_', ' ')),
    labels: { ...ejeX.labels, formatter: v => Number(v).toFixed(0) },
    /* Conteos enteros: sin esto el eje repite ticks (0,1,1,2,2,3,3). */
    tickAmount: Math.min(4, Math.max(1, ...data.value.solicitudes_estado.map(f => f.cantidad))),
  },
  yaxis: {
    labels: { style: { colors: TINTA.secundaria, fontSize: '10.5px', fontFamily: FUENTE }, maxWidth: 110 },
  },
  legend: { show: false },
  tooltip: { ...base.tooltip, y: { formatter: v => v + ' solicitud(es)' } },
}))

/* ── 9. Lotes por vencer (barras, colores de estado) ──────────── */
const serieVencimientos = computed(() => ([
  { name: 'Lotes', data: data.value.vencimientos.map(f => f.cantidad) },
]))

const opcionesVencimientos = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [ESTADO.critico, ESTADO.serio, ESTADO.alerta, ESTADO.bueno],
  plotOptions: {
    bar: {
      columnWidth: '48%',
      borderRadius: 4,
      borderRadiusApplication: 'end',
      distributed: true,
      dataLabels: { position: 'top' },
    },
  },
  dataLabels: {
    enabled: true,
    offsetY: -20,
    style: { fontSize: '12px', fontFamily: FUENTE, fontWeight: 700, colors: [TINTA.secundaria] },
  },
  xaxis: { ...ejeX, categories: data.value.vencimientos.map(f => f.etiqueta) },
  yaxis: {
    ...ejeY,
    labels: { ...ejeY.labels, formatter: v => Number(v).toFixed(0) },
    tickAmount: Math.min(4, Math.max(1, ...data.value.vencimientos.map(f => f.cantidad))),
  },
  legend: { show: false },
  tooltip: { ...base.tooltip, y: { formatter: v => v + ' lote(s) con existencia' } },
}))
</script>

<style scoped>
.dashboard {
  background: #f9f9f7;
}

/* ── Bienvenida sin permiso de panel ───────────────────────── */
.bienvenida__logo {
  width: 100%;
  max-width: 220px;
  height: auto;
  object-fit: contain;
}

/* ── Filtros ───────────────────────────────────────────────── */
.dashboard__toggle {
  border: 1px solid #e1e0d9;
  border-radius: 8px;
  background: #fff;
}
.dashboard__fecha {
  width: 150px;
}

/* ── Resumen en línea (sustituye a las tarjetas) ───────────── */
.resumen {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 4px 16px;
  padding: 4px 2px 8px;
  font-size: 12px;
  color: #52514e;
}
.resumen__rango {
  font-weight: 700;
  color: #0e7a5f;
}
.resumen__dato {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
  white-space: nowrap;
}
.resumen__label {
  font-size: 10.5px;
  text-transform: uppercase;
  letter-spacing: .03em;
  color: #898781;
}
.resumen__value {
  font-weight: 700;
  color: #0b0b0b;
}
.resumen__dato--alerta .resumen__value { color: #d03b3b; }

/* ── Paneles de gráficos ───────────────────────────────────── */
.panel {
  padding: 8px 6px 2px;
  border-radius: 10px;
  height: 100%;
}
.panel__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 0 6px 2px;
}
.panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #0b0b0b;
}
.panel__sub {
  font-size: 11px;
  color: #898781;
  margin-top: 1px;
}
.panel__total {
  font-size: 14px;
  font-weight: 700;
  color: #0b0b0b;
  white-space: nowrap;
}
.panel__unit {
  font-size: 11px;
  font-weight: 600;
  color: #898781;
}
.vacio {
  padding: 40px 12px;
  text-align: center;
  font-size: 12px;
  color: #898781;
}
.stock {
  max-height: 210px;
  overflow-y: auto;
}
</style>
