<template>
  <div class="decGrid">
    <div class="card">
      <h3>Fases del protocolo DEC</h3>
      <p class="subtle q-mt-none">Desregulación emocional y conductual: del aula al equipo de apoyo.</p>
      <div v-for="(f, i) in cat?.fases || []" :key="f.value" class="fase" :class="`f${i}`">
        <b>{{ i + 1 }}. {{ f.label }}</b>
        <div>{{ f.desc }}</div>
      </div>
    </div>

    <div>
      <div class="card q-mb-md">
        <h3>Activar protocolo · formulario rápido</h3>
        <q-form class="q-gutter-sm q-mt-xs" @submit.prevent="activar">
          <q-select
            v-model="form.estudiante_id" :options="estudiantes" option-value="id" :option-label="e => `${e.nombre} · ${e.curso}`"
            emit-value map-options use-input input-debounce="300" label="Estudiante" outlined dense @filter="buscar"
            :hint="soloReporta ? 'Solo estudiantes de sus cursos (carga o jefatura)' : ''"
          >
            <template #no-option><q-item><q-item-section class="subtle">Escriba el nombre del estudiante</q-item-section></q-item></template>
          </q-select>
          <q-select v-model="form.intensidad" :options="cat?.intensidades || []" emit-value map-options label="Nivel de intensidad" outlined dense />
          <q-input v-model="form.descripcion" type="textarea" rows="3" label="Descripción breve de la situación" outlined dense placeholder="Qué ocurrió, dónde, quiénes estaban presentes…" />
          <q-input v-model="form.lugar" label="Lugar" outlined dense placeholder="Sala, patio, comedor…" />
          <q-toggle v-model="form.familia_contactada" label="Ya se contactó a la familia" />
          <div class="subtle">Adulto que reporta: <b>{{ $auth.user.name }}</b></div>
          <q-btn type="submit" no-caps unelevated class="btnPrimary full-width" icon="fas fa-bell" label="Registrar y notificar al equipo" :loading="guardando" :disable="!form.estudiante_id || !form.descripcion" />
          <p class="subtle text-center q-mb-none">
            Queda en Seguimiento para Convivencia y Orientación, en fase de contención.
            <template v-if="form.intensidad === 'alta'"><b class="rojo">Intensidad alta: avise además de inmediato a Dirección.</b></template>
          </p>
        </q-form>
      </div>

      <div class="card">
        <div class="cardHead"><h3>Mis reportes de este año</h3><span class="badge">{{ reportes.length }}</span></div>
        <div v-if="!reportes.length" class="subtle">Aún no ha activado el protocolo este año.</div>
        <div v-for="r in reportes" :key="r.id" class="reporte">
          <div>
            <b>{{ r.estudiante }}</b> <span class="subtle">· {{ r.curso }}</span>
            <small class="subtle">{{ fechaCl(r.fecha) }} · intensidad {{ r.intensidad }}{{ r.fase ? ` · ${etiqueta(cat?.fases, r.fase)}` : '' }}</small>
          </div>
          <span class="badge" :class="CLASE_ESTADO_ALERTA[r.estado]">{{ etiqueta(cat?.estados_alerta, r.estado) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { fechaCl } from '../../composables/fechas'
import { useCatalogosEmocional, etiqueta, CLASE_ESTADO_ALERTA } from '../../composables/emocional'

const emit = defineEmits(['activado'])
const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()

const vacio = () => ({ estudiante_id: null, intensidad: 'baja', descripcion: '', lugar: '', familia_contactada: false })
const form = ref(vacio())
const estudiantes = ref([])
const reportes = ref([])
const guardando = ref(false)
const soloReporta = computed(() => !proxy.$auth.hasPermission('emocional.ver'))

function buscar (texto, update) {
  proxy.$axios.get('emocional/protocolo/estudiantes', { params: { q: texto || undefined } })
    .then(res => update(() => { estudiantes.value = res.data }))
    .catch(() => update(() => { estudiantes.value = [] }))
}

function cargarReportes () {
  proxy.$axios.get('emocional/protocolo/mis-reportes').then(res => { reportes.value = res.data }).catch(() => {})
}

async function activar () {
  guardando.value = true
  try {
    const { data } = await proxy.$axios.post('emocional/protocolo', form.value)
    proxy.$alert.success(data.message)
    form.value = vacio()
    cargarReportes()
    emit('activado', data.id)
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

onMounted(cargarReportes)
</script>

<style scoped>
.decGrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start }
.fase { padding: 13px 14px; border-left: 4px solid; border-radius: 0 10px 10px 0; margin-bottom: 10px; font-size: 13px; line-height: 1.5 }
.fase div { color: #5f7487; margin-top: 3px }
.fase.f0 { border-color: #7c3aed; background: #faf5ff }
.fase.f1 { border-color: #d97706; background: #fffbeb }
.fase.f2 { border-color: #0284c7; background: #eff6ff }
.fase.f3 { border-color: #059669; background: #f0fdf4 }
.reporte { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eef3f7; font-size: 13px }
.reporte small { display: block }
.rojo { color: #bd1f2d }
@media (max-width: 900px) { .decGrid { grid-template-columns: 1fr } }
</style>
