<template>
  <div class="permisosCell">
    <span class="permCount" :class="{ vacio: !permisos.length }">
      {{ permisos.length }} {{ permisos.length === 1 ? 'permiso' : 'permisos' }}
    </span>
    <span v-for="permiso in permisos.slice(0, visibles)" :key="permiso" class="badge blue">
      {{ etiqueta(permiso) }}
    </span>
    <span v-if="permisos.length > visibles" class="badge mas" @click="emit('ver')">
      +{{ permisos.length - visibles }} más
      <q-tooltip>
        <div v-for="permiso in permisos" :key="permiso">• {{ etiqueta(permiso) }}</div>
      </q-tooltip>
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useCatalogos } from '../composables/useCrud'

/** Resumen compacto de permisos: cantidad, los primeros y "+N más" con la lista completa. */
defineProps({
  permisos: { type: Array, required: true },
  visibles: { type: Number, default: 2 },
})
const emit = defineEmits(['ver'])

const catalogos = useCatalogos()
const etiquetas = computed(() => Object.fromEntries(
  catalogos.value.permisos.flatMap(g => g.permisos.map(p => [p.name, p.label])),
))
const etiqueta = name => etiquetas.value[name] || name
</script>

<style scoped>
.permisosCell { display: flex; align-items: center; gap: 4px; flex-wrap: nowrap }
.permCount { font-size: 11px; font-weight: 800; color: #0f4f85; min-width: 74px }
.permCount.vacio { color: #9aabb8 }
.badge.mas { cursor: pointer; background: #f1f5f9 }
</style>
