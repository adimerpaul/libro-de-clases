<template>
  <q-btn v-if="visibles.length" flat round dense size="sm" icon="fas fa-ellipsis-vertical" class="btnAcciones" aria-label="Acciones">
    <q-tooltip>Acciones</q-tooltip>
    <q-menu auto-close anchor="bottom left" self="top left">
      <q-list dense style="min-width: 190px">
        <template v-for="a in visibles" :key="a.label">
          <q-separator v-if="a.peligro && visibles.length > 1" />
          <q-item clickable :to="a.to" :class="a.peligro ? 'text-negative' : ''" @click="a.click && a.click()">
            <q-item-section avatar style="min-width: 28px"><q-icon :name="a.icon" size="14px" /></q-item-section>
            <q-item-section>{{ a.label }}</q-item-section>
          </q-item>
        </template>
      </q-list>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { computed, getCurrentInstance } from 'vue'

// Botón ⋮ de la primera columna de las tablas de administración.
// acciones: [{ label, icon, click?, to?, peligro?, permiso?, si? }]
// Se ocultan las acciones sin permiso o con si === false; sin ninguna, no se muestra el botón.
const props = defineProps({ acciones: { type: Array, required: true } })

const { proxy } = getCurrentInstance()
const visibles = computed(() => props.acciones.filter(a =>
  a.si !== false && (!a.permiso || proxy.$auth.hasPermission(a.permiso))))
</script>

<style scoped>
.btnAcciones { color: #33536d }
</style>
