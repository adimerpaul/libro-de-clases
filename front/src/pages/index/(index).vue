<template>
  <q-page class="page">
    <div class="roleBanner">
      <div>
        <h2>{{ perfil.titulo }}</h2>
        <p>{{ perfil.desc }}</p>
      </div>
      <span class="roleBadge">{{ perfil.rol }}</span>
    </div>

    <div class="heroPanel">
      <div>
        <span class="eyebrow heroEyebrow">Ecosistema educativo 4.2</span>
        <h2>La misma información sirve para enseñar, acompañar, gestionar y comunicar.</h2>
        <p>
          La arquitectura evita registros paralelos: el OA alimenta planificación y evaluación; la asistencia
          nutre libro e Inspectoría; PIE adapta la experiencia; UTP monitorea; la familia recibe solo lo que corresponde.
        </p>
        <div class="flow">
          <template v-for="(paso, i) in flujo" :key="paso">
            <span v-if="i" class="flowArrow">→</span>
            <span class="flowStep">{{ paso }}</span>
          </template>
        </div>
      </div>
      <div class="heroQuick">
        <div class="quick"><b>{{ disponibles }}</b><small>módulos disponibles para ti</small></div>
        <div class="quick"><b>{{ pendientes }}</b><small>módulos pendientes</small></div>
        <div class="quick"><b>{{ $auth.user.permisos?.length || 0 }}</b><small>permisos activos</small></div>
        <div class="quick"><b>1</b><small>fuente de datos compartida</small></div>
      </div>
    </div>

    <h3 class="tituloTareas">Lo que requiere tu rol</h3>
    <div class="taskGrid">
      <div v-for="t in tareas" :key="t.titulo" class="taskCard">
        <div class="taskTop">
          <q-icon :name="t.icon" class="ticon" />
          <span v-if="t.pendiente" class="badge">Pendiente</span>
        </div>
        <h3>{{ t.titulo }}</h3>
        <p>{{ t.desc }}</p>
        <q-btn
          v-if="t.puede" :to="t.link" :label="t.pendiente ? 'Ver' : 'Abrir'" no-caps unelevated dense
          class="btnPrimary btnAbrir" icon-right="fas fa-arrow-right"
        />
        <span v-else class="soon">Requiere permiso</span>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance } from 'vue'
import { MENU, MODULOS, perfilDe } from '../../composables/menu'

defineOptions({ name: 'InicioPage' })
definePage({ meta: { title: 'Inicio' } })

const { proxy } = getCurrentInstance()

const flujo = ['OA', 'Planificación', 'Clase', 'Evaluación', 'Libro', 'Familia / Gestión']

const perfil = computed(() => perfilDe(proxy.$auth.user))

// Tareas del perfil (ui3/app.js homePage), enlazadas a su módulo del menú
// (las tareas de módulos que no están en el menú se omiten)
const tareas = computed(() => perfil.value.tareas.filter(([key]) => MODULOS[key]).map(([key, titulo, desc]) => {
  const m = MODULOS[key]
  return { titulo, desc, icon: m.icon, link: m.link, pendiente: m.pendiente, puede: !m.permiso || proxy.$auth.hasPermission(m.permiso) }
}))

// Perfiles con `modulos` (apoderado) solo cuentan los suyos
const modulos = computed(() => MENU.flatMap(g => g.links)
  .filter(l => l.key !== 'inicio' && (!perfil.value.modulos || perfil.value.modulos.includes(l.key))))
const disponibles = computed(() => modulos.value.filter(l => !l.pendiente && (!l.permiso || proxy.$auth.hasPermission(l.permiso))).length)
const pendientes = computed(() => modulos.value.filter(l => l.pendiente).length)
</script>

<style scoped>
.heroEyebrow { color: #ffd6dc }
.heroPanel h2 { font-weight: 800 }
.flow { margin-top: 14px }
.flowStep { color: #15324a }
.flowArrow { color: #cbdbe8 }
.tituloTareas { font-size: 17px; font-weight: 800; margin: 18px 0 10px; line-height: 1.3 }
.taskTop { display: flex; justify-content: space-between; align-items: center }
.ticon { font-size: 22px; color: var(--role) }
.taskCard { display: flex; flex-direction: column }
.taskCard p { flex: 1 }
.btnAbrir { align-self: flex-start; margin-top: 10px; padding: 2px 12px; font-size: 12px }
.soon { margin-top: 10px; font-size: 11px; font-weight: 700; color: #6d8090 }
</style>
