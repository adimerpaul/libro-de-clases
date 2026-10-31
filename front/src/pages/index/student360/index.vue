<template>
  <q-page class="page">
    <div class="roleBanner">
      <div>
        <h2>Student 360</h2>
        <p>Vista longitudinal de cada estudiante: asistencia y puntualidad, asignaturas y evaluaciones de su curso, trayectoria y apoyos. Los dominios sensibles se muestran solo según perfil.</p>
      </div>
      <span class="roleBadge"><q-icon name="fas fa-tower-broadcast" size="11px" class="q-mr-xs" />Asistencia RFID · propuesta</span>
    </div>

    <!-- Filtros en una fila -->
    <div class="card filtros">
      <q-select v-model="f.anio" :options="anios" dense outlined label="Año" class="fAnio" @update:model-value="cambiarAnio" />
      <q-select
        v-model="f.curso_id" :options="opcionesCurso" emit-value map-options clearable dense outlined label="Curso"
        class="fSel" @update:model-value="cargar"
      />
      <q-select
        v-model="f.asignatura_id" :options="opcionesAsignatura" emit-value map-options clearable dense outlined label="Asignatura"
        class="fSel" @update:model-value="cargar"
      >
        <template #option="{ opt, itemProps }">
          <q-item v-bind="itemProps" dense>
            <q-item-section avatar class="puntoItem"><span class="punto" :style="{ background: opt.color }" /></q-item-section>
            <q-item-section>{{ opt.label }}</q-item-section>
          </q-item>
        </template>
      </q-select>
      <q-input v-model="f.q" dense outlined debounce="350" placeholder="Nombre o RUN" class="fBuscar" @update:model-value="cargar">
        <template #prepend><q-icon name="fas fa-magnifying-glass" size="13px" /></template>
      </q-input>
      <q-btn-toggle
        v-model="f.alerta" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
        :options="[{ label: 'Todos', value: null }, { label: 'Con alertas', value: 'con' }, { label: 'Sin alertas', value: 'sin' }]"
        @update:model-value="cargar"
      />
    </div>

    <!-- Indicadores -->
    <div class="kpis">
      <div class="kpi" style="--acc: #1f6fb2">
        <q-icon name="fas fa-user-graduate" class="kpiIcon" />
        <b>{{ r.estudiantes ?? '—' }}</b>
        <small>estudiantes · {{ r.mujeres ?? 0 }} M / {{ r.hombres ?? 0 }} H</small>
      </div>
      <div class="kpi" style="--acc: #17703f">
        <div class="anillo" :style="{ '--p': r.asistencia ?? 0 }"><span>{{ pct(r.asistencia) }}</span></div>
        <small>asistencia promedio</small>
      </div>
      <div class="kpi" style="--acc: #bd1f2d">
        <q-icon name="fas fa-circle-exclamation" class="kpiIcon" />
        <b>{{ r.criticos ?? '—' }}</b>
        <small>bajo 85 % (promoción)</small>
      </div>
      <div class="kpi" style="--acc: #b56b12">
        <q-icon name="fas fa-triangle-exclamation" class="kpiIcon" />
        <b>{{ r.en_riesgo ?? '—' }}</b>
        <small>con alertas</small>
      </div>
      <div class="kpi" style="--acc: #6d4cc7">
        <q-icon name="fas fa-clock" class="kpiIcon" />
        <b>{{ r.atrasos ?? '—' }}</b>
        <small>atrasos en el año</small>
      </div>
      <div class="kpi" style="--acc: #16866b">
        <q-icon name="fas fa-puzzle-piece" class="kpiIcon" />
        <b>{{ r.pie ?? '—' }}</b>
        <small>con apoyo PIE</small>
      </div>
    </div>

    <!-- Distribución por tramo de asistencia -->
    <div v-if="r.estudiantes" class="card tramosCard">
      <div class="cardHead">
        <h3>Distribución de asistencia</h3>
        <span class="subtle">{{ r.estudiantes }} estudiantes · {{ f.anio }}</span>
      </div>
      <div class="tramosBar" role="img" :aria-label="tramos.map(t => `${t.label}: ${t.n}`).join(', ')">
        <div
          v-for="t in tramos.filter(t => t.n)" :key="t.key" class="tramo" :class="t.key"
          :style="{ flexGrow: t.n }" @click="soloTramo(t.key)"
        >
          <span v-if="t.n / r.estudiantes > 0.06">{{ t.n }}</span>
          <q-tooltip>{{ t.label }}: {{ t.n }} estudiantes ({{ Math.round(t.n * 100 / r.estudiantes) }} %)</q-tooltip>
        </div>
      </div>
      <div class="leyenda">
        <button v-for="t in tramos" :key="t.key" class="ley" :class="{ off: tramo && tramo !== t.key }" @click="soloTramo(t.key)">
          <span class="sw" :class="t.key" /><q-icon :name="t.icon" size="11px" />{{ t.label }} <b>{{ t.n }}</b>
        </button>
        <q-btn v-if="tramo" flat dense no-caps size="sm" icon="fas fa-xmark" label="Quitar filtro" @click="tramo = null" />
      </div>
    </div>

    <!-- Con asignatura: cursos y su docente -->
    <div v-if="f.asignatura_id && cursosRes.length" class="asigStrip">
      <span class="punto grande" :style="{ background: asignaturaSel?.color }" />
      <b>{{ asignaturaSel?.nombre }}</b>
      <span v-for="c in cursosRes" :key="c.id" class="chipCurso">{{ c.nombre }} · {{ c.docente || 'sin docente' }}</span>
    </div>

    <!-- Nómina -->
    <div class="card">
      <div class="cardHead">
        <h3>Estudiantes <span class="badge blue q-ml-sm">{{ visibles.length }}</span></h3>
        <div class="row items-center q-gutter-sm">
          <q-select v-model="orden" :options="ordenes" emit-value map-options dense borderless class="ordenSel" />
          <q-btn-toggle
            v-model="vista" unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
            :options="[{ icon: 'fas fa-grip', value: 'tarjetas' }, { icon: 'fas fa-list', value: 'tabla' }]"
          />
        </div>
      </div>

      <div v-if="loading" class="grid">
        <q-skeleton v-for="i in 8" :key="i" height="132px" style="border-radius: 16px" />
      </div>
      <div v-else-if="!visibles.length" class="vacio">
        <q-icon name="fas fa-users-slash" size="30px" />
        <b>Sin estudiantes para estos filtros.</b>
      </div>

      <div v-else-if="vista === 'tarjetas'" class="grid">
        <router-link v-for="e in visibles" :key="e.id" :to="enlace(e)" class="est" :class="nivel(e)">
          <div class="estTop">
            <div class="avatar" :class="nivel(e)">{{ iniciales(e) }}</div>
            <div class="estNombre">
              <b>{{ e.nombre_completo }}</b>
              <span class="subtle">{{ e.curso.nombre }} · N° {{ e.numero_lista }}</span>
            </div>
          </div>
          <div class="barra">
            <div class="relleno" :class="nivel(e)" :style="{ width: `${e.asistencia.porcentaje ?? 0}%` }" />
            <div class="marca" style="left: 85%" />
          </div>
          <div class="estPie">
            <span class="pctTxt"><b>{{ pct(e.asistencia.porcentaje) }}</b> asistencia</span>
            <span class="subtle"><q-icon name="far fa-clock" size="11px" /> {{ e.asistencia.atrasos }}</span>
          </div>
          <div class="chips">
            <span v-if="e.pie" class="badge violeta">PIE</span>
            <span v-for="(a, i) in e.alertas" :key="i" class="badge" :class="a.nivel === 'critica' ? 'red' : 'warn'">
              <q-icon :name="a.nivel === 'critica' ? 'fas fa-circle-exclamation' : 'fas fa-triangle-exclamation'" size="9px" />
              {{ a.nivel === 'critica' ? 'Bajo 85 %' : a.texto.includes('atrasos') ? 'Atrasos' : 'En riesgo' }}
            </span>
            <span v-if="e.asignatura" class="badge">{{ e.asignatura.instrumentos }} evaluaciones</span>
          </div>
        </router-link>
      </div>

      <div v-else class="tablaWrap">
        <table class="tabla">
          <thead>
            <tr><th>N°</th><th>Estudiante</th><th>Curso</th><th>RUN</th><th>Asistencia</th><th>Días</th><th>Atrasos</th><th>Alertas</th></tr>
          </thead>
          <tbody>
            <tr v-for="e in visibles" :key="e.id" @click="$router.push(enlace(e))">
              <td class="centro">{{ e.numero_lista }}</td>
              <td><b>{{ e.nombre_completo }}</b> <span v-if="e.pie" class="badge violeta">PIE</span></td>
              <td class="nowrap">{{ e.curso.nombre }}</td>
              <td class="nowrap">{{ formatoRun(e.run) }}</td>
              <td class="nowrap">
                <span class="dot" :class="nivel(e)" />{{ pct(e.asistencia.porcentaje) }}
              </td>
              <td class="centro">{{ e.asistencia.dias }}/{{ e.asistencia.lectivos }}</td>
              <td class="centro">{{ e.asistencia.atrasos }}</td>
              <td>
                <span v-for="(a, i) in e.alertas" :key="i" class="badge q-mr-xs" :class="a.nivel === 'critica' ? 'red' : 'warn'">{{ a.texto }}</span>
                <span v-if="!e.alertas.length" class="subtle">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="nota">
        <q-icon name="fas fa-circle-info" size="12px" />
        Asistencia calculada desde las marcas RFID (día lectivo = día hábil con marcas en el curso). Es una propuesta; la asistencia oficial la valida una persona autorizada.
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { formatoRun } from '../../../composables/formato'

definePage({ meta: { title: 'Student 360', permiso: ['student360.ver', 'clases.dictar'] } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()

const f = ref({
  anio: Number(route.query.anio) || new Date().getFullYear(),
  curso_id: Number(route.query.curso_id) || null,
  asignatura_id: Number(route.query.asignatura_id) || null,
  q: route.query.q || '',
  alerta: route.query.alerta || null,
})
const filtros = ref({ anios: [], cursos: [], asignaturas: [] })
const datos = ref({ resumen: {}, cursos: [], estudiantes: [] })
const loading = ref(false)
const vista = ref('tarjetas')
const orden = ref('lista')
const tramo = ref(null)

const ordenes = [
  { label: 'Por curso y N° de lista', value: 'lista' },
  { label: 'Menor asistencia primero', value: 'asistencia' },
  { label: 'Más atrasos primero', value: 'atrasos' },
  { label: 'Por apellido', value: 'apellido' },
]

const r = computed(() => datos.value.resumen || {})
const cursosRes = computed(() => datos.value.cursos || [])
const anios = computed(() => (filtros.value.anios.length ? filtros.value.anios : [f.value.anio]))
// Con asignatura elegida, solo los cursos que la tienen
const opcionesCurso = computed(() => filtros.value.cursos
  .filter(c => !f.value.asignatura_id || c.asignaturas.includes(f.value.asignatura_id))
  .map(c => ({ label: `${c.nombre} (${c.estudiantes_count})`, value: c.id })))
const opcionesAsignatura = computed(() => {
  const curso = filtros.value.cursos.find(c => c.id === f.value.curso_id)
  return filtros.value.asignaturas
    .filter(a => !curso || curso.asignaturas.includes(a.id))
    .map(a => ({ label: a.nombre, value: a.id, color: a.color || '#1f6fb2' }))
})
const asignaturaSel = computed(() => filtros.value.asignaturas.find(a => a.id === f.value.asignatura_id))

const tramos = computed(() => {
  const t = r.value.tramos || {}
  return [
    { key: 'sobre_95', label: '95 % o más', n: t.sobre_95 || 0, icon: 'fas fa-circle-check' },
    { key: 'de_90_a_95', label: '90 – 95 %', n: t.de_90_a_95 || 0, icon: 'far fa-circle-check' },
    { key: 'de_85_a_90', label: '85 – 90 % (riesgo)', n: t.de_85_a_90 || 0, icon: 'fas fa-triangle-exclamation' },
    { key: 'bajo_85', label: 'Bajo 85 %', n: t.bajo_85 || 0, icon: 'fas fa-circle-exclamation' },
    { key: 'sin_datos', label: 'Sin marcas', n: t.sin_datos || 0, icon: 'far fa-circle' },
  ]
})

function tramoDe (p) {
  if (p === null || p === undefined) return 'sin_datos'
  if (p >= 95) return 'sobre_95'
  if (p >= 90) return 'de_90_a_95'
  if (p >= 85) return 'de_85_a_90'
  return 'bajo_85'
}

const visibles = computed(() => {
  const lista = datos.value.estudiantes.filter(e => !tramo.value || tramoDe(e.asistencia.porcentaje) === tramo.value)
  const p = e => e.asistencia.porcentaje ?? 101
  const ordenar = {
    lista: null,
    asistencia: (a, b) => p(a) - p(b),
    atrasos: (a, b) => b.asistencia.atrasos - a.asistencia.atrasos,
    apellido: (a, b) => a.nombre_completo.localeCompare(b.nombre_completo, 'es'),
  }[orden.value]
  return ordenar ? [...lista].sort(ordenar) : lista
})

const pct = v => (v === null || v === undefined ? '—' : `${String(v).replace('.', ',')} %`)
const nivel = e => ({ sobre_95: 'ok', de_90_a_95: 'bien', de_85_a_90: 'riesgo', bajo_85: 'critico', sin_datos: 'nd' })[tramoDe(e.asistencia.porcentaje)]
const iniciales = e => `${e.nombres?.[0] || ''}${e.apellido_paterno?.[0] || ''}`.toUpperCase()
const enlace = e => ({ path: `/student360/${e.id}`, query: { anio: f.value.anio, ...(f.value.asignatura_id ? { asignatura_id: f.value.asignatura_id } : {}) } })

function soloTramo (key) {
  tramo.value = tramo.value === key ? null : key
}

function params () {
  return Object.fromEntries(Object.entries(f.value).filter(([, v]) => v !== null && v !== ''))
}

function cargar () {
  loading.value = true
  router.replace({ query: params() })
  proxy.$axios.get('student360', { params: params() })
    .then(res => { datos.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { loading.value = false })
}

function cargarFiltros () {
  return proxy.$axios.get('student360/filtros', { params: { anio: f.value.anio } })
    .then(res => {
      filtros.value = res.data
      if (f.value.curso_id && !res.data.cursos.some(c => c.id === f.value.curso_id)) f.value.curso_id = null
    })
}

function cambiarAnio () {
  f.value.curso_id = null
  cargarFiltros().then(cargar)
}

onMounted(() => {
  cargarFiltros().catch(err => proxy.$alert.error(mensajeError(err))).finally(cargar)
})
</script>

<style scoped>
/* Colores de estado (con ícono y texto: nunca solo color) */
.page { --ok: #17703f; --bien: #4f9a6d; --riesgo: #c98a12; --critico: #bd1f2d; --nd: #a9b8c6 }

.filtros { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 14px 0; padding: 12px 14px }
.fAnio { width: 100px }
.fSel { min-width: 180px; flex: 1 1 180px; max-width: 260px }
.fBuscar { flex: 2 1 200px }
.puntoItem { min-width: 0; padding-right: 8px }
.punto { display: inline-block; width: 10px; height: 10px; border-radius: 50% }
.punto.grande { width: 14px; height: 14px }

.kpis { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 14px }
.kpi {
  position: relative; overflow: hidden; background: #fff; border: 1px solid #d9e3ec; border-radius: 18px;
  padding: 14px 16px; display: flex; flex-direction: column; gap: 2px; box-shadow: 0 7px 22px rgba(22, 52, 80, .055); min-height: 108px
}
.kpi:before { content: ""; position: absolute; left: 0; top: 0; right: 0; height: 4px; background: var(--acc) }
.kpiIcon { color: var(--acc); font-size: 16px; margin-bottom: 4px }
.kpi b { font-size: 28px; font-weight: 900; letter-spacing: -.03em; color: #0b2440; line-height: 1.1 }
.kpi small { color: #6d8090; font-size: 11px; line-height: 1.3 }
.anillo {
  --p: 0; width: 58px; height: 58px; border-radius: 50%; display: grid; place-items: center; margin-bottom: 4px;
  background: conic-gradient(var(--acc) calc(var(--p) * 1%), #e8eef3 0)
}
.anillo span { width: 46px; height: 46px; border-radius: 50%; background: #fff; display: grid; place-items: center; font-size: 11px; font-weight: 900; color: #0b2440 }

.tramosCard { margin-bottom: 14px }
.tramosBar { display: flex; gap: 2px; height: 30px; border-radius: 8px; overflow: hidden; margin: 4px 0 10px }
.tramo { min-width: 6px; display: grid; place-items: center; color: #fff; font-size: 11px; font-weight: 900; cursor: pointer; transition: filter .15s }
.tramo:hover { filter: brightness(1.08) }
.tramo.sobre_95, .sw.sobre_95 { background: var(--ok) }
.tramo.de_90_a_95, .sw.de_90_a_95 { background: var(--bien) }
.tramo.de_85_a_90, .sw.de_85_a_90 { background: var(--riesgo) }
.tramo.bajo_85, .sw.bajo_85 { background: var(--critico) }
.tramo.sin_datos, .sw.sin_datos { background: var(--nd) }
.leyenda { display: flex; flex-wrap: wrap; gap: 6px 14px; align-items: center }
.ley { display: inline-flex; align-items: center; gap: 6px; border: 0; background: none; font-size: 12px; color: #415c72; cursor: pointer; padding: 2px 0 }
.ley b { color: #0b2440 }
.ley.off { opacity: .4 }
.sw { width: 10px; height: 10px; border-radius: 3px }

.asigStrip { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; padding: 10px 14px; border-radius: 14px; background: var(--roleSoft); border: 1px dashed color-mix(in srgb, var(--role) 40%, #cfd8e3); font-size: 13px }
.chipCurso { background: #fff; border: 1px solid #d9e3ec; border-radius: 999px; padding: 3px 10px; font-size: 11px; color: #415c72 }

.ordenSel { min-width: 200px; font-size: 12px }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px }
.est {
  display: flex; flex-direction: column; gap: 10px; padding: 14px; border-radius: 16px; border: 1px solid #e1e9f0;
  background: linear-gradient(180deg, #fff, #fbfdff); color: inherit; text-decoration: none; position: relative;
  transition: transform .15s, box-shadow .15s, border-color .15s
}
.est:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(22, 52, 80, .11); border-color: #c9def0 }
.est.critico { border-color: #f3c9cf }
.estTop { display: flex; gap: 10px; align-items: center }
.avatar { width: 42px; height: 42px; flex: none; border-radius: 50%; display: grid; place-items: center; font-weight: 900; font-size: 13px; color: #fff; background: var(--nd) }
.avatar.ok { background: linear-gradient(135deg, #1f8a4f, var(--ok)) }
.avatar.bien { background: linear-gradient(135deg, #63ad80, var(--bien)) }
.avatar.riesgo { background: linear-gradient(135deg, #e0a52c, var(--riesgo)) }
.avatar.critico { background: linear-gradient(135deg, #d93a47, var(--critico)) }
.estNombre { display: flex; flex-direction: column; min-width: 0 }
.estNombre b { font-size: 13px; color: #0b2440; line-height: 1.25 }
.estNombre .subtle { font-size: 11px }
.barra { position: relative; height: 6px; border-radius: 99px; background: #e8eef3 }
.relleno { height: 100%; border-radius: 99px; background: var(--nd) }
.relleno.ok { background: var(--ok) }
.relleno.bien { background: var(--bien) }
.relleno.riesgo { background: var(--riesgo) }
.relleno.critico { background: var(--critico) }
.marca { position: absolute; top: -3px; width: 2px; height: 12px; background: #0b244066; border-radius: 2px }
.estPie { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #415c72 }
.pctTxt b { color: #0b2440; font-size: 14px }
.chips { display: flex; flex-wrap: wrap; gap: 5px; min-height: 20px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.badge.warn { background: #fff5e7; border-color: #f3dfa8; color: #8a5a0c }

.tablaWrap { overflow-x: auto }
.tabla { width: 100%; border-collapse: collapse; font-size: 12px }
.tabla th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #6d8090; padding: 8px; border-bottom: 1px solid #e1e9f0 }
.tabla td { padding: 9px 8px; border-bottom: 1px solid #eef3f7 }
.tabla tbody tr { cursor: pointer }
.tabla tbody tr:hover td { background: #f6faff }
.centro { text-align: center }
.nowrap { white-space: nowrap }
.dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; background: var(--nd) }
.dot.ok { background: var(--ok) }
.dot.bien { background: var(--bien) }
.dot.riesgo { background: var(--riesgo) }
.dot.critico { background: var(--critico) }

.vacio { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px; color: #6d8090 }
.nota { display: flex; gap: 8px; align-items: flex-start; margin-top: 14px; font-size: 11px; color: #6d8090; line-height: 1.5 }

@media (max-width: 1200px) { .kpis { grid-template-columns: repeat(3, 1fr) } }
@media (max-width: 640px) {
  .kpis { grid-template-columns: repeat(2, 1fr) }
  .fSel, .fBuscar { max-width: none; flex: 1 1 100% }
  .ordenSel { min-width: 150px }
}
</style>
