<template>
  <div v-if="!items.length" class="vacio"><b>Sin registros.</b></div>
  <div v-else class="timeline">
    <div v-for="s in items" :key="s.id" class="evento">
      <div class="eventoIcono"><q-icon :name="ICONOS[s.tipo] || 'far fa-clipboard'" size="15px" /></div>
      <div class="eventoCuerpo">
        <div class="eventoHead">
          <b>{{ s.tipo }}</b>
          <span v-if="s.area" class="badge blue">{{ s.area }}</span>
          <span v-if="s.duracion_minutos" class="badge">{{ s.duracion_minutos }} min</span>
          <span class="subtle">{{ fecha(s.fecha) }}<template v-if="s.autor"> · {{ s.autor.name }}</template></span>
        </div>
        <p>{{ s.descripcion }}</p>
        <p v-if="s.acuerdos" class="acuerdos"><b>Acuerdos:</b> {{ s.acuerdos }}</p>
      </div>
      <div v-if="$auth.hasPermission('pie.editar')" class="eventoAcciones">
        <q-btn flat dense round size="sm" icon="fas fa-pen" @click="emit('editar', s)"><q-tooltip>Editar</q-tooltip></q-btn>
        <q-btn flat dense round size="sm" icon="far fa-trash-can" color="negative" @click="emit('eliminar', s)"><q-tooltip>Eliminar</q-tooltip></q-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
import { fecha } from '../composables/pie'

// Registros de la bitácora PIE (pages/index/pie/[id].vue: pestañas Bitácora y Familia).
defineProps({ items: { type: Array, required: true } })
const emit = defineEmits(['editar', 'eliminar'])

const ICONOS = {
  'Sesión en aula de recursos': 'fas fa-chalkboard',
  'Co-docencia': 'fas fa-people-arrows',
  'Observación de aula': 'far fa-eye',
  'Entrevista familiar': 'fas fa-house-chimney-user',
  'Reunión de equipo': 'fas fa-people-group',
  'Evaluación / reevaluación': 'fas fa-clipboard-check',
  'Derivación externa': 'fas fa-share-from-square',
}
</script>

<style scoped>
.timeline { display: grid; gap: 8px }
.evento { display: grid; grid-template-columns: 36px 1fr auto; gap: 10px; padding: 10px; border: 1px solid #e0e8ef; border-radius: 12px; background: #fff }
.eventoIcono { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--roleSoft, #e8f8f3); color: var(--role, #16866b) }
.eventoHead { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; font-size: 13px }
.eventoCuerpo p { margin: 4px 0 0; font-size: 12px; color: #33536d; line-height: 1.5; white-space: pre-line }
.eventoCuerpo .acuerdos { color: #5f7487 }
.eventoAcciones { display: flex; gap: 2px; align-items: flex-start; color: #33536d }
.vacio { display: grid; place-items: center; padding: 26px; color: #6d8090 }
</style>
