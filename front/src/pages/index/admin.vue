<template>
  <q-page class="page">
    <div class="adminTabs">
      <q-tabs dense no-caps inline-label align="left" active-color="white" indicator-color="transparent" outside-arrows mobile-arrows>
        <q-route-tab v-for="t in tabsVisibles" :key="t.to" :to="t.to" :icon="t.icon" :label="t.label" class="adminTab" />
      </q-tabs>
    </div>
    <router-view />
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance } from 'vue'
import { MODULOS_ADMIN } from '../../composables/adminModulos'

// Menú Administrador: cada pestaña es una ruta hija (admin/<pestaña>.vue)
// (definePage no admite variables: misma lista que PERMISOS_ADMIN)
definePage({
  meta: {
    title: 'Administrador',
    permiso: ['cursos.ver', 'asignaturas.ver', 'docentes.ver', 'asignaciones.ver', 'usuarios.ver', 'roles.ver'],
  },
})

const { proxy } = getCurrentInstance()

// Cada pestaña se muestra a quien puede ver su módulo (MODULOS_ADMIN)
const tabsVisibles = computed(() => MODULOS_ADMIN.filter(t => proxy.$auth.hasPermission(`${t.modulo}.ver`)))
</script>

<style scoped>
.adminTabs {
  background: #fff; border: 1px solid #d9e3ec; border-radius: 18px;
  box-shadow: 0 7px 22px rgba(22, 52, 80, .055);
  padding: 6px; margin-bottom: 18px;
}
.adminTab { border-radius: 12px; color: #33536d; font-weight: 700; min-height: 38px }
.adminTab.q-tab--active { background: #0f4f85; color: #fff }
</style>
