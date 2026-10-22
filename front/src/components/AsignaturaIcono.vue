<template>
  <span class="asigIcono" :style="{ background: fondo, color }">
    <q-icon :name="icono" size="14px" />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  nombre: { type: String, default: '' },
  color: { type: String, default: null },
})

// Ícono según el nombre de la asignatura; el orden importa ("Ciencias para la Ciudadanía" antes que "Ciencias")
const ICONOS = [
  ['ciudadania', 'fas fa-leaf', 'ciencias'],
  ['ciudadan', 'fas fa-scale-balanced'],
  ['educacion fisica', 'fas fa-person-running'],
  ['lengua', 'fas fa-book-open'],
  ['matemat', 'fas fa-calculator'],
  ['historia', 'fas fa-landmark'],
  ['ciencia', 'fas fa-flask'],
  ['biolog', 'fas fa-dna'],
  ['quimic', 'fas fa-flask-vial'],
  ['fisica', 'fas fa-atom'],
  ['ingles', 'fas fa-language'],
  ['artes', 'fas fa-palette'],
  ['musica', 'fas fa-music'],
  ['tecnolog', 'fas fa-laptop-code'],
  ['orientacion', 'fas fa-compass'],
  ['filosof', 'fas fa-brain'],
  ['religion', 'fas fa-hands-praying'],
]

const icono = computed(() => {
  const n = props.nombre.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
  const hit = ICONOS.find(([clave, , tambien]) => n.includes(clave) && (!tambien || n.includes(tambien)))
  return hit ? hit[1] : 'fas fa-book'
})

const color = computed(() => props.color || '#5f7d8c')
const fondo = computed(() => `${color.value}1f`)
</script>

<style scoped>
.asigIcono {
  display: inline-grid; place-items: center;
  width: 30px; height: 30px; border-radius: 9px;
  margin-right: 10px; vertical-align: middle; flex: none;
}
</style>
