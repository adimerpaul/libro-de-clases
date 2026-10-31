<template>
  <div>
    <div class="card q-mb-md">
      <div class="subtle">Alineado a Currículum Nacional (Orientación + OAT) · PNCE 2024-2030 · Plan de Reactivación Educativa · Estrategia de Salud Mental</div>
      <h3 class="q-mt-sm q-mb-xs">Objetivo general</h3>
      <p class="objetivo">
        Desarrollar de manera gradual y sistemática las competencias socioemocionales de los y las estudiantes (autoconciencia,
        autorregulación, conciencia social, habilidades de relación y toma de decisiones responsables), promoviendo el bienestar,
        la salud mental y un clima escolar de cuidado colectivo.
      </p>
    </div>

    <div class="tres q-mb-md">
      <div class="card"><small class="subtle">Competencias prioritarias</small><b class="num">{{ cat?.competencias.length || 5 }}</b></div>
      <div class="card"><small class="subtle">Celdas del plan con sesiones</small><b class="num">{{ cubiertas }} / {{ totalCeldas }}</b></div>
      <div class="card"><small class="subtle">Cobertura</small><b class="num">{{ plan?.cobertura ?? '—' }}%</b></div>
    </div>

    <div class="card q-mb-md">
      <div class="cardHead">
        <h3>Estructura por competencia y nivel</h3>
        <span class="subtle">El número indica las sesiones realizadas o en curso este año en ese tramo</span>
      </div>
      <q-inner-loading :showing="!plan" />
      <div class="tablaScroll">
        <table v-if="plan && cat" class="matriz">
          <thead>
            <tr><th>Competencia</th><th v-for="t in cat.tramos" :key="t.value">{{ t.label }}</th></tr>
          </thead>
          <tbody>
            <tr v-for="c in cat.competencias" :key="c.value">
              <td><span class="dot" :style="{ background: c.color }" /><b>{{ c.label }}</b></td>
              <td v-for="t in cat.tramos" :key="t.value" :class="{ vacia: !plan.celdas[c.value][t.value].sesiones }">
                {{ plan.celdas[c.value][t.value].foco }}
                <span class="badge q-ml-xs" :class="plan.celdas[c.value][t.value].sesiones ? 'ok' : ''">
                  {{ plan.celdas[c.value][t.value].sesiones || 'sin sesiones' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="eeGrid2">
      <div class="card">
        <h3>Con Convivencia Educativa (Ley 21.809)</h3>
        <ul class="lista">
          <li>El aprendizaje socioemocional es parte explícita del Plan de Gestión de Convivencia.</li>
          <li>Las acciones formativas alimentan los indicadores de bienestar del establecimiento.</li>
          <li>El protocolo DEC se articula con los protocolos de acoso o violencia cuando corresponde.</li>
          <li>Convivencia y el equipo socioemocional comparten alertas y seguimientos, según permisos.</li>
        </ul>
      </div>
      <div class="card">
        <h3>Con Formación Ciudadana (Ley 20.911)</h3>
        <ul class="lista">
          <li>Empatía, respeto y toma de decisiones responsables son ejes comunes.</li>
          <li>Los proyectos de FC pueden incorporar componentes socioemocionales (cuidado del espacio común + conciencia social).</li>
          <li>Educación Ciudadana 3°–4° medio se potencia con Rutas Sana Mente y reflexiones emocionales.</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { useCatalogosEmocional } from '../../composables/emocional'
import { mensajeError } from '../../composables/useCrud'

const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()
const plan = ref(null)

const celdas = computed(() => (plan.value ? Object.values(plan.value.celdas).flatMap(t => Object.values(t)) : []))
const totalCeldas = computed(() => celdas.value.length || 15)
const cubiertas = computed(() => celdas.value.filter(c => c.sesiones > 0).length)

onMounted(() => {
  proxy.$axios.get('emocional/plan').then(res => { plan.value = res.data }).catch(e => proxy.$alert.error(mensajeError(e)))
})
</script>

<style scoped>
.objetivo { font-size: 14px; line-height: 1.6; margin: 0 }
.tres { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px }
.num { display: block; font-size: 26px; color: #0b2440 }
.tablaScroll { overflow-x: auto }
.matriz { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 720px }
.matriz th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6d8090; padding: 8px; border-bottom: 2px solid #e3eaf1 }
.matriz td { padding: 10px 8px; border-bottom: 1px solid #eef3f7; vertical-align: top; line-height: 1.45 }
.matriz td.vacia { color: #8a9bab }
.dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px }
.eeGrid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px }
.lista { margin: 0; padding-left: 18px; font-size: 13px; line-height: 1.8; color: #4b6478 }
@media (max-width: 900px) { .tres, .eeGrid2 { grid-template-columns: 1fr } }
</style>
