<template>
  <q-page class="page">
    <q-inner-loading :showing="!d" />
    <template v-if="d">
      <div class="eeHero">
        <span class="foto">{{ iniciales }}</span>
        <div class="col">
          <span class="eyebrow">Ficha socioemocional</span>
          <h2>{{ d.estudiante.nombre_completo }}</h2>
          <p>
            {{ d.curso ? `${d.curso.nombre} · ${etiqueta(cat?.tramos, d.curso.tramo)}` : 'Sin matrícula activa este año' }}
            · RUN {{ formatoRun(d.estudiante.run) }}
            <template v-if="d.curso?.profesor_jefe"> · Prof. jefe {{ d.curso.profesor_jefe }}</template>
            <span v-if="d.estudiante.pie" class="badge violeta q-ml-xs">PIE</span>
          </p>
        </div>
        <div class="acciones">
          <q-btn flat no-caps color="white" icon="fas fa-arrow-left" label="Volver" @click="$router.back()" />
          <q-btn v-if="$auth.hasPermission('emocional.crear') && d.curso" no-caps unelevated class="btnBlanco" icon="fas fa-plus" label="Nueva alerta" @click="nuevaAlerta" />
        </div>
      </div>

      <div class="kpis">
        <div v-for="dim in cat?.dimensiones || []" :key="dim.value" class="kpi">
          <small>{{ dim.label.replace('Aprendizaje socioemocional ', '') }}</small>
          <b :style="{ color: dim.color }">{{ d.ultimo?.dimensiones[dim.value] ?? '—' }}{{ d.ultimo?.dimensiones[dim.value] != null ? '%' : '' }}</b>
        </div>
        <div class="kpi">
          <small>Alertas abiertas</small>
          <b :class="{ rojo: abiertas }">{{ abiertas }}</b>
        </div>
      </div>

      <div class="grid2">
        <!-- Competencias -->
        <div class="card">
          <div class="cardHead">
            <h3>Competencias</h3>
            <span class="subtle">{{ d.ultimo ? `${etiqueta(cat?.periodos, d.ultimo.periodo)} ${d.anio} · ${fechaCl(d.ultimo.fecha)}` : `Sin diagnóstico en ${d.anio}` }}</span>
          </div>
          <div v-for="c in cat?.competencias || []" :key="c.value" class="barra">
            <div class="barraTop">
              <span>{{ c.label }}</span>
              <span>
                <small class="subtle q-mr-sm">{{ d.trabajado[c.value] }} {{ d.trabajado[c.value] === 1 ? 'sesión' : 'sesiones' }} en su curso</small>
                <b>{{ d.ultimo?.[c.value] ?? '—' }}</b>
              </span>
            </div>
            <div class="track"><div class="fill" :style="{ width: `${d.ultimo?.[c.value] || 0}%`, background: c.color }" /></div>
            <div v-if="d.curso" class="foco">{{ cat?.plan?.[c.value]?.[d.curso.tramo] }}</div>
          </div>
          <div v-if="d.ultimo?.observacion" class="obs"><b>Observación:</b> {{ d.ultimo.observacion }}</div>
        </div>

        <!-- Historial de diagnósticos -->
        <div class="card">
          <div class="cardHead"><h3>Historial de diagnósticos</h3></div>
          <div v-if="!d.diagnosticos.length" class="subtle">Sin diagnósticos registrados.</div>
          <q-markup-table v-else flat dense class="tabla">
            <thead><tr><th class="text-left">Periodo</th><th>Personal</th><th>Comunitario</th><th>Ciudadano</th><th>Promedio</th></tr></thead>
            <tbody>
              <tr v-for="x in d.diagnosticos" :key="x.id">
                <td>{{ etiqueta(cat?.periodos, x.periodo) }} {{ x.anio }}<div class="subtle">{{ x.curso?.nivel }} {{ x.curso?.letra }} · {{ fechaCl(x.fecha) }}</div></td>
                <td v-for="dim in ['personal', 'comunitario', 'ciudadano']" :key="dim" class="text-center"><span class="badge" :class="claseLogro(x.dimensiones[dim])">{{ x.dimensiones[dim] ?? '—' }}</span></td>
                <td class="text-center"><b>{{ x.promedio ?? '—' }}</b></td>
              </tr>
            </tbody>
          </q-markup-table>
        </div>
      </div>

      <!-- Seguimientos -->
      <div class="card q-mt-md">
        <div class="cardHead">
          <h3>Seguimiento de bienestar <span class="badge q-ml-sm">{{ d.alertas.length }}</span></h3>
          <span class="subtle"><q-icon name="fas fa-lock" size="11px" /> Confidencial</span>
        </div>
        <div v-if="!d.alertas.length" class="subtle">Sin alertas ni seguimientos registrados.</div>
        <div v-for="a in d.alertas" :key="a.id" class="alerta">
          <div class="row items-center q-gutter-xs">
            <span class="badge" :class="CLASE_TIPO_ALERTA[a.tipo]">{{ etiqueta(cat?.tipos_alerta, a.tipo) }}</span>
            <span class="badge" :class="CLASE_ESTADO_ALERTA[a.estado]">{{ etiqueta(cat?.estados_alerta, a.estado) }}</span>
            <span v-if="a.fase" class="badge">DEC · {{ etiqueta(cat?.fases, a.fase) }}</span>
            <span class="subtle">desde {{ fechaCl(a.fecha) }} · intensidad {{ a.intensidad }} · {{ a.responsable?.name || 'sin responsable' }}</span>
            <q-space />
            <q-btn flat dense no-caps color="primary" size="sm" label="Abrir en seguimiento" :to="{ path: '/emocional', query: { tab: 'seguimiento', alerta: a.id } }" />
          </div>
          <div class="q-mt-xs">{{ a.descripcion }}</div>
          <div v-for="i in a.intervenciones" :key="i.id" class="interv">
            <b>{{ fechaCl(i.fecha) }}</b> — {{ etiqueta(cat?.tipos_intervencion, i.tipo) }}: {{ i.descripcion }}
            <span v-if="i.acuerdos" class="subtle"> · Acuerdos: {{ i.acuerdos }}</span>
          </div>
        </div>
      </div>

      <!-- Sesiones del curso -->
      <div class="card q-mt-md">
        <div class="cardHead"><h3>Sesiones de su curso <span class="badge q-ml-sm">{{ d.sesiones.length }}</span></h3></div>
        <div v-if="!d.sesiones.length" class="subtle">Sin sesiones registradas para su curso.</div>
        <div v-for="s in d.sesiones" :key="s.id" class="sesion">
          <span class="dot" :style="{ background: colorCompetencia(cat, s.competencia) }" />
          <div class="col">
            <b>{{ s.titulo }}</b>
            <small class="subtle">{{ etiqueta(cat?.competencias, s.competencia) }} · {{ s.tipo }} · {{ fechaCl(s.fecha) }}{{ s.objetivo ? ` · ${s.objetivo.codigo}` : '' }}</small>
          </div>
          <span class="badge" :class="CLASE_ESTADO_SESION[s.estado]">{{ etiqueta(cat?.estados_sesion, s.estado) }}</span>
        </div>
      </div>

      <EeAlertaForm ref="formRef" @guardada="cargar" />
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { fechaCl } from '../../../composables/fechas'
import { formatoRun } from '../../../composables/formato'
import {
  useCatalogosEmocional, etiqueta, colorCompetencia, claseLogro,
  CLASE_ESTADO_ALERTA, CLASE_TIPO_ALERTA, CLASE_ESTADO_SESION,
} from '../../../composables/emocional'
import EeAlertaForm from '../../../components/emocional/EeAlertaForm.vue'

definePage({ meta: { title: 'Ficha socioemocional', permiso: 'emocional.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const cat = useCatalogosEmocional()
const d = ref(null)
const formRef = ref(null)

const iniciales = computed(() => `${d.value?.estudiante.nombres?.[0] || ''}${d.value?.estudiante.apellido_paterno?.[0] || ''}`)
const abiertas = computed(() => (d.value?.alertas || []).filter(a => a.estado !== 'cerrada').length)

async function cargar () {
  try {
    d.value = (await proxy.$axios.get(`emocional/estudiantes/${route.params.id}`)).data
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  }
}

function nuevaAlerta () {
  formRef.value.abrir(null, { estudiante_id: d.value.estudiante.id, nombre: d.value.estudiante.nombre_completo, curso: d.value.curso?.nombre })
}

watch(() => route.params.id, id => { if (id) cargar() })
onMounted(cargar)
</script>

<style scoped>
.eeHero { background: linear-gradient(135deg, #4c1d95, #7c3aed 55%, #1f3f6b); color: #fff; border-radius: 20px; padding: 18px 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap }
.foto { width: 70px; height: 80px; border-radius: 14px; background: linear-gradient(135deg, #ede9fe, #c4b5fd); display: grid; place-items: center; font-size: 24px; font-weight: 900; color: #4c1d95; border: 4px solid #ffffffaa }
.eyebrow { font-size: 10px; letter-spacing: .12em; text-transform: uppercase; font-weight: 900; color: #e9d5ff }
.eeHero h2 { margin: 2px 0 4px; font-size: 24px; font-weight: 800; line-height: 1.2 }
.eeHero p { margin: 0; color: #ede9fe; font-size: 12.5px }
.acciones { display: flex; gap: 8px; align-items: center }
.btnBlanco { background: #fff; color: #5b21b6; border-radius: 11px; font-weight: 800 }
.kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 14px 0 }
.kpi { background: #fff; border: 1px solid #e5def9; border-radius: 14px; padding: 12px }
.kpi small { display: block; color: #6d8090; font-size: 11.5px; text-transform: capitalize }
.kpi b { font-size: 24px }
.kpi b.rojo { color: #bd1f2d }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start }
.barra { margin-bottom: 13px }
.barraTop { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px }
.track { height: 9px; background: #eef2f7; border-radius: 99px; overflow: hidden }
.fill { height: 100%; border-radius: 99px }
.foco { font-size: 11.5px; color: #6d8090; margin-top: 3px }
.obs { font-size: 12.5px; background: #faf7ff; border-radius: 10px; padding: 8px 10px; margin-top: 6px }
.tabla th { font-size: 11px; color: #6d8090; text-transform: uppercase }
.alerta { border: 1px solid #e3eaf1; border-left: 4px solid #7c3aed; border-radius: 0 12px 12px 0; padding: 10px 12px; margin-bottom: 10px; font-size: 13px }
.interv { background: #f8fafc; border-radius: 8px; padding: 6px 9px; margin-top: 6px; font-size: 12.5px }
.sesion { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eef3f7; font-size: 13px }
.sesion small { display: block }
.dot { flex: none; width: 10px; height: 10px; border-radius: 50% }
@media (max-width: 1000px) { .grid2 { grid-template-columns: 1fr } .kpis { grid-template-columns: repeat(2, 1fr) } }
</style>
