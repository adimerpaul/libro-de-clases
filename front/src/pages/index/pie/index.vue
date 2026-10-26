<template>
  <q-page class="page">
    <div class="roleBanner">
      <div>
        <h2>PIE e inclusión</h2>
        <p>Evaluación integral, barreras, equipo de apoyo, plan de apoyo, PACI, expediente y bitácora de cada estudiante del Programa de Integración Escolar.</p>
      </div>
      <q-btn
        v-if="$auth.hasPermission('pie.crear')" no-caps unelevated class="btnPrimary" icon="fas fa-user-plus"
        label="Ingresar estudiante" @click="formAbierto = true"
      />
    </div>

    <div class="kpiLine q-mb-md">
      <div v-for="k in kpis" :key="k.label" class="kpiMini">
        <q-icon :name="k.icon" class="kpiIcon" />
        <b>{{ k.valor }}</b>
        <small>{{ k.label }}</small>
      </div>
    </div>

    <div class="pieGrid">
      <div class="card">
        <div class="cardHead">
          <h3>Estudiantes en seguimiento PIE <span class="badge ok q-ml-sm">{{ items.length }}</span></h3>
        </div>
        <div class="filtros">
          <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="width: 120px" @update:model-value="cargar" />
          <q-select v-model="estado" :options="opcionesEstado" emit-value map-options clearable dense outlined label="Estado" style="min-width: 150px" />
          <q-select v-model="tipo" :options="opcionesTipo" emit-value map-options clearable dense outlined label="Tipo de NEE" style="min-width: 160px" />
          <q-select v-model="curso" :options="cursos" clearable dense outlined label="Curso" style="min-width: 130px" />
          <q-input v-model="texto" dense outlined debounce="200" placeholder="Nombre, RUN o diagnóstico" class="col">
            <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
          </q-input>
        </div>

        <div v-if="loading" class="lista">
          <q-skeleton v-for="i in 5" :key="i" height="74px" style="border-radius: 13px" />
        </div>
        <div v-else-if="!items.length" class="vacio">
          <q-icon name="fas fa-puzzle-piece" size="30px" />
          <b>{{ casos.length ? 'Sin casos para estos filtros.' : `Aún no hay estudiantes en el PIE ${anio}.` }}</b>
          <q-btn v-if="!casos.length && $auth.hasPermission('pie.crear')" no-caps flat color="primary" icon="fas fa-user-plus" label="Ingresar el primero" @click="formAbierto = true" />
        </div>
        <div v-else class="lista">
          <router-link v-for="c in items" :key="c.id" :to="`/pie/${c.id}`" class="pieCase">
            <div class="pieAvatar" :class="c.tipo_nee">{{ iniciales(c.estudiante) }}</div>
            <div class="info">
              <div class="nombre">
                <b>{{ c.estudiante.nombre_completo }}</b>
                <span class="subtle">{{ c.curso?.nombre || 'Sin matrícula' }} · {{ formatoRun(c.estudiante.run) }}</span>
              </div>
              <div class="diag">
                <span class="badge" :class="TIPOS_NEE[c.tipo_nee].badge">{{ TIPOS_NEE[c.tipo_nee].label }}</span>
                {{ c.diagnostico }}
              </div>
              <small v-if="c.barreras" class="barrera"><b>Barrera:</b> {{ c.barreras }}</small>
            </div>
            <div class="lado">
              <span class="pill" :class="ESTADOS_PIE[c.estado].pill">{{ ESTADOS_PIE[c.estado].label }}</span>
              <span class="subtle meta">
                <span><q-icon name="far fa-folder-open" /> {{ c.documentos_count }}</span>
                <span><q-icon name="far fa-clipboard" /> {{ c.seguimientos_count }}</span>
              </span>
              <span v-if="reevaluacion(c)" class="badge" :class="reevaluacion(c).clase">{{ reevaluacion(c).texto }}</span>
            </div>
          </router-link>
        </div>
      </div>

      <div class="columnaLateral">
        <div class="card">
          <div class="cardHead"><h3>Flujo de evaluación y diagnóstico integral</h3></div>
          <div v-for="(p, i) in FLUJO" :key="p[0]" class="paso">
            <div class="rowIcon">{{ i + 1 }}</div>
            <div class="rowText"><b>{{ p[0] }}</b><small>{{ p[1] }}</small></div>
          </div>
        </div>

        <div class="card">
          <div class="cardHead"><h3>Por diagnóstico</h3><span class="subtle">{{ anio }}</span></div>
          <div v-if="!porDiagnostico.length" class="subtle">Sin casos vigentes.</div>
          <div v-for="d in porDiagnostico" :key="d.label" class="barra">
            <div class="barraTexto"><span>{{ d.label }}</span><b>{{ d.n }}</b></div>
            <div class="barraFondo"><div class="barraValor" :class="d.tipo" :style="{ width: `${(d.n / maxDiagnostico) * 100}%` }" /></div>
          </div>
        </div>
      </div>
    </div>

    <PieCasoForm v-model="formAbierto" :anio="anio" @guardado="c => $router.push(`/pie/${c.id}`)" />
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../../composables/useCrud'
import { formatoRun } from '../../../composables/formato'
import { ESTADOS_PIE, TIPOS_NEE, iniciales, diasHasta, useCatalogosPie } from '../../../composables/pie'
import PieCasoForm from '../../../components/PieCasoForm.vue'

definePage({ meta: { title: 'PIE / Inclusión', permiso: 'pie.ver' } })

const { proxy } = getCurrentInstance()
useCatalogosPie(proxy)

const FLUJO = [
  ['Detección de barreras', 'Pruebas, observación pedagógica y desempeño en contexto.'],
  ['Diagnóstico multidimensional', 'Articulación con psicología, fonoaudiología, terapia ocupacional y equipo directivo.'],
  ['Plan de apoyo', 'DUA, adecuaciones, PACI cuando corresponda, co-docencia y seguimiento.'],
]

const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(actual)
const casos = ref([])
const loading = ref(false)
const formAbierto = ref(false)

const estado = ref(null)
const tipo = ref(null)
const curso = ref(null)
const texto = ref('')

const opcionesEstado = Object.entries(ESTADOS_PIE).map(([value, e]) => ({ value, label: e.label }))
const opcionesTipo = Object.entries(TIPOS_NEE).map(([value, t]) => ({ value, label: t.label }))
const cursos = computed(() => [...new Set(casos.value.map(c => c.curso?.nombre).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es', { numeric: true })))

const normalizar = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

const items = computed(() => {
  const q = normalizar(texto.value)
  const run = texto.value.replace(/[^0-9kK]/g, '')
  return casos.value.filter(c =>
    (!estado.value || c.estado === estado.value) &&
    (!tipo.value || c.tipo_nee === tipo.value) &&
    (!curso.value || c.curso?.nombre === curso.value) &&
    (!q || normalizar(`${c.estudiante.nombre_completo} ${c.diagnostico}`).includes(q) || (run && c.estudiante.run.includes(run))))
})

const vigentes = computed(() => casos.value.filter(c => c.estado !== 'egresado'))

const kpis = computed(() => [
  { label: 'estudiantes en el programa', valor: vigentes.value.length, icon: 'fas fa-puzzle-piece' },
  { label: 'NEE transitorias', valor: vigentes.value.filter(c => c.tipo_nee === 'transitoria').length, icon: 'fas fa-arrows-rotate' },
  { label: 'NEE permanentes', valor: vigentes.value.filter(c => c.tipo_nee === 'permanente').length, icon: 'fas fa-infinity' },
  { label: 'en evaluación', valor: casos.value.filter(c => c.estado === 'en_evaluacion').length, icon: 'fas fa-magnifying-glass-chart' },
  { label: 'reevaluaciones en 60 días', valor: vigentes.value.filter(c => { const d = diasHasta(c.fecha_reevaluacion); return d !== null && d <= 60 }).length, icon: 'far fa-calendar-check' },
])

const porDiagnostico = computed(() => {
  const cuenta = {}
  for (const c of vigentes.value) {
    cuenta[c.diagnostico] ??= { label: c.diagnostico, tipo: c.tipo_nee, n: 0 }
    cuenta[c.diagnostico].n++
  }
  return Object.values(cuenta).sort((a, b) => b.n - a.n)
})
const maxDiagnostico = computed(() => Math.max(1, ...porDiagnostico.value.map(d => d.n)))

function reevaluacion (c) {
  if (c.estado === 'egresado') return null
  const d = diasHasta(c.fecha_reevaluacion)
  if (d === null || d > 60) return null
  if (d < 0) return { texto: 'Reevaluación vencida', clase: 'red' }
  return { texto: d === 0 ? 'Reevaluación hoy' : `Reevaluación en ${d} días`, clase: 'amber' }
}

function cargar () {
  loading.value = true
  return proxy.$axios.get('pie/casos', { params: { anio: anio.value } })
    .then(res => { casos.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { loading.value = false })
}

onMounted(cargar)
</script>

<style scoped>
/* ui3: .kpiLine, .pieCase, .pieAvatar, .pill, .rowIcon */
.kpiLine { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px }
.kpiMini { background: #fff; border: 1px solid #dbe5ec; border-radius: 14px; padding: 12px 14px; position: relative }
.kpiMini b { display: block; font-size: 24px; font-weight: 800; color: #0b2440 }
.kpiMini small { color: #6d8090; font-size: 11px }
.kpiIcon { position: absolute; right: 14px; top: 14px; color: var(--role, #16866b); font-size: 16px; opacity: .8 }

.pieGrid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 14px; align-items: start }
.columnaLateral { display: grid; gap: 14px }
.filtros { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 12px }
.lista { display: grid; gap: 8px }

.pieCase {
  display: grid; grid-template-columns: 52px 1fr auto; gap: 12px; align-items: center; border: 1px solid #dce5ec;
  border-radius: 13px; padding: 10px 12px; background: #fff; color: inherit; text-decoration: none; transition: border-color .15s, box-shadow .15s;
}
.pieCase:hover { border-color: #9fc3e2; box-shadow: 0 6px 18px #0f4f8514 }
.pieAvatar { width: 46px; height: 46px; border-radius: 14px; background: #eaf8f3; display: grid; place-items: center; font-weight: 900; color: #16634f }
.pieAvatar.permanente { background: #f1edff; color: #5a3fb0 }
.info { min-width: 0 }
.nombre { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap }
.nombre b { font-size: 14px }
.diag { font-size: 12px; color: #33536d; margin: 3px 0 }
.barrera { display: block; font-size: 11px; color: #6d8090; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.lado { display: flex; flex-direction: column; align-items: flex-end; gap: 5px }
.meta { display: flex; gap: 10px; font-size: 11px }

.pill { padding: 5px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; background: #edf3f7; color: #486477; white-space: nowrap }
.pill.green { background: #eaf8f1; color: #20704f }
.pill.amber { background: #fff4e2; color: #a66412 }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.badge.amber { background: #fff4e2; border-color: #f5dcb2; color: #a66412 }

.paso { display: flex; gap: 10px; align-items: flex-start; padding: 10px; border: 1px solid #e0e8ef; border-radius: 12px; margin-bottom: 8px }
.rowIcon { width: 30px; height: 30px; border-radius: 10px; display: grid; place-items: center; background: var(--roleSoft, #e8f8f3); color: var(--role, #16866b); font-weight: 900; flex: none }
.rowText b { display: block; font-size: 13px }
.rowText small { color: #6d8090; font-size: 11px; line-height: 1.4 }

.barra { margin-bottom: 9px }
.barraTexto { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; margin-bottom: 3px }
.barraFondo { height: 7px; background: #edf3f7; border-radius: 99px; overflow: hidden }
.barraValor { height: 100%; background: #16866b; border-radius: 99px }
.barraValor.permanente { background: #6d4cc7 }

.vacio { display: grid; place-items: center; gap: 10px; padding: 40px; color: #6d8090; text-align: center }

@media (max-width: 1200px) { .kpiLine { grid-template-columns: repeat(3, 1fr) } }
@media (max-width: 1000px) { .pieGrid { grid-template-columns: 1fr } }
@media (max-width: 640px) {
  .kpiLine { grid-template-columns: 1fr 1fr }
  .pieCase { grid-template-columns: 46px 1fr }
  .lado { grid-column: 1 / -1; flex-direction: row; justify-content: flex-start; flex-wrap: wrap }
}
</style>
