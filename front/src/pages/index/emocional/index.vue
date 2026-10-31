<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Educación emocional</h1>
        <p>
          Plan socioemocional por competencias, sesiones por curso, seguimiento individual de bienestar, protocolo de desregulación (DEC)
          y diagnóstico tipo DIA de cada estudiante.
        </p>
      </div>
    </div>

    <div class="eeTabs">
      <button v-for="t in visibles" :key="t.value" type="button" class="eeTab" :class="{ active: tab === t.value }" @click="tab = t.value">
        <q-icon :name="t.icon" size="13px" class="q-mr-xs" />{{ t.label }}
      </button>
    </div>

    <EeDashboard v-if="tab === 'dashboard'" @ir="t => (tab = t)" />
    <EePlan v-else-if="tab === 'plan'" />
    <EeSesiones v-else-if="tab === 'sesiones'" />
    <EeSeguimiento v-else-if="tab === 'seguimiento'" />
    <EeProtocolo v-else-if="tab === 'protocolo'" @activado="alActivar" />
    <EeDiagnostico v-else-if="tab === 'diagnostico'" />
    <EeEstudiantes v-else-if="tab === 'estudiantes'" />
    <EeOrientacion v-else-if="tab === 'orientacion'" />
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EeDashboard from '../../../components/emocional/EeDashboard.vue'
import EePlan from '../../../components/emocional/EePlan.vue'
import EeSesiones from '../../../components/emocional/EeSesiones.vue'
import EeSeguimiento from '../../../components/emocional/EeSeguimiento.vue'
import EeProtocolo from '../../../components/emocional/EeProtocolo.vue'
import EeDiagnostico from '../../../components/emocional/EeDiagnostico.vue'
import EeEstudiantes from '../../../components/emocional/EeEstudiantes.vue'
import EeOrientacion from '../../../components/emocional/EeOrientacion.vue'

definePage({ meta: { title: 'Educación emocional', permiso: ['emocional.ver', 'emocional.reportar', 'clases.dictar'] } })
defineOptions({ name: 'EducacionEmocionalPage' })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()

// Sin "emocional.ver" (docente que solo reporta) se ve únicamente el protocolo DEC
const TABS = [
  { value: 'dashboard', label: 'Dashboard', icon: 'fas fa-heart-pulse', ver: true },
  { value: 'plan', label: 'Plan socioemocional', icon: 'fas fa-clipboard-list', ver: true },
  { value: 'sesiones', label: 'Sesiones y actividades', icon: 'far fa-calendar-check', ver: true },
  { value: 'seguimiento', label: 'Seguimiento individual', icon: 'fas fa-user-shield', ver: true },
  { value: 'protocolo', label: 'Protocolo DEC', icon: 'fas fa-triangle-exclamation', ver: false },
  { value: 'diagnostico', label: 'Diagnóstico (tipo DIA)', icon: 'fas fa-chart-column', ver: true },
  { value: 'estudiantes', label: 'Estudiantes', icon: 'fas fa-user-graduate', ver: true },
  { value: 'orientacion', label: 'Orientación (OA)', icon: 'fas fa-graduation-cap', ver: true },
]

const puedeVer = computed(() => proxy.$auth.hasPermission('emocional.ver'))
const visibles = computed(() => TABS.filter(t => !t.ver || puedeVer.value))
const inicial = visibles.value.some(t => t.value === route.query.tab) ? route.query.tab : visibles.value[0].value
const tab = ref(inicial)

watch(tab, t => router.replace({ query: { ...route.query, tab: t, alerta: t === 'seguimiento' ? route.query.alerta : undefined } }))

// Quien también gestiona los seguimientos salta a la alerta recién creada
async function alActivar (id) {
  if (!puedeVer.value) return
  await router.replace({ query: { tab: 'seguimiento', alerta: id } })
  tab.value = 'seguimiento'
}
</script>

<style scoped>
.eeTabs { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 14px }
.eeTab { border: 1px solid #ddd3fb; background: #fff; color: #4c3a8a; border-radius: 999px; padding: 8px 13px; font: inherit; font-size: 12px; font-weight: 800; cursor: pointer }
.eeTab:hover { background: #faf7ff }
.eeTab.active { background: #7c3aed; color: #fff; border-color: #7c3aed; box-shadow: 0 6px 16px #7c3aed33 }
</style>
