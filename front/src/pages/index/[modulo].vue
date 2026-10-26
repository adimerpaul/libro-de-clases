<template>
  <q-page class="page">
    <template v-if="modulo">
      <div class="roleBanner">
        <div>
          <h2><q-icon :name="modulo.icon" class="q-mr-sm" />{{ modulo.title }}</h2>
          <p>{{ modulo.desc }}</p>
        </div>
        <span class="roleBadge">Pendiente</span>
      </div>

      <div class="card pendienteCard">
        <q-icon name="fas fa-person-digging" class="pendienteIcon" />
        <h3>Módulo en construcción</h3>
        <p>
          Este módulo forma parte del Ecosistema 4.2 y todavía no está disponible. Cuando se habilite,
          aparecerá aquí con los permisos que correspondan a tu rol.
        </p>
        <q-btn to="/" label="Volver al inicio" no-caps unelevated class="btnPrimary" icon="fas fa-arrow-left" />
      </div>
    </template>

    <div v-else class="card pendienteCard">
      <q-icon name="far fa-circle-question" class="pendienteIcon" />
      <h3>Esta página no existe</h3>
      <q-btn to="/" label="Volver al inicio" no-caps unelevated class="btnPrimary" icon="fas fa-arrow-left" />
    </div>
  </q-page>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { MODULOS } from '../../composables/menu'

defineOptions({ name: 'ModuloPendientePage' })

const route = useRoute()

// Solo los módulos marcados como pendientes en el menú; cualquier otra ruta es "no existe"
const modulo = computed(() => {
  const m = MODULOS[route.params.modulo]
  return m?.pendiente ? m : null
})
</script>

<style scoped>
.pendienteCard { text-align: center; padding: 42px 24px; max-width: 640px; margin: 24px auto }
.pendienteIcon { font-size: 42px; color: var(--role) }
.pendienteCard h3 { font-size: 18px; font-weight: 800; margin: 14px 0 6px }
.pendienteCard p { color: #6d8090; line-height: 1.55; margin: 0 0 18px }
</style>
