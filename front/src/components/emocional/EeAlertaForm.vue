<template>
  <q-dialog v-model="abierto">
    <q-card class="formCard alertaCard">
      <q-form @submit.prevent="guardar">
        <q-card-section class="formTitle">{{ form.id ? 'Editar alerta de bienestar' : 'Nueva alerta de bienestar' }}</q-card-section>
        <q-card-section class="formGrid">
          <q-select
            v-if="!form.id" v-model="form.estudiante_id" :options="estudiantes" option-value="id" :option-label="e => `${e.nombre} · ${e.curso}`"
            emit-value map-options use-input input-debounce="300" label="Estudiante" outlined dense class="full" @filter="buscar"
          >
            <template #no-option><q-item><q-item-section class="subtle">Escriba el nombre del estudiante</q-item-section></q-item></template>
          </q-select>
          <div v-else class="full"><b>{{ nombre }}</b></div>

          <q-select v-model="form.tipo" :options="cat?.tipos_alerta || []" emit-value map-options label="Tipo de alerta" outlined dense />
          <q-select v-model="form.intensidad" :options="cat?.intensidades || []" emit-value map-options label="Intensidad" outlined dense />
          <q-select v-model="form.estado" :options="cat?.estados_alerta || []" emit-value map-options label="Estado" outlined dense />
          <q-select v-if="form.tipo === 'desregulacion'" v-model="form.fase" :options="cat?.fases || []" emit-value map-options label="Fase protocolo DEC" outlined dense />
          <q-input v-model="form.fecha" type="date" label="Fecha de inicio" stack-label outlined dense />
          <q-input v-model="form.proxima_revision" type="date" label="Próxima revisión" stack-label outlined dense />
          <q-select v-model="form.responsable_id" :options="cat?.responsables || []" option-value="id" option-label="name" emit-value map-options clearable label="Responsable" outlined dense />
          <q-input v-model="form.equipo" label="Equipo" outlined dense placeholder="Psicóloga + Profesor jefe" />
          <q-input v-model="form.descripcion" type="textarea" autogrow label="Situación observada" outlined dense class="full" />
          <q-input v-model="form.lugar" label="Lugar (opcional)" outlined dense />
          <q-toggle v-model="form.familia_contactada" label="Familia contactada" />
          <q-input v-if="form.estado === 'derivacion'" v-model="form.derivacion" label="Derivación a" outlined dense class="full" placeholder="CESFAM, COSAM, OPD…" />
          <template v-if="form.estado === 'cerrada'">
            <q-input v-model="form.fecha_cierre" type="date" label="Fecha de cierre" stack-label outlined dense />
            <q-input v-model="form.motivo_cierre" label="Motivo de cierre" outlined dense />
          </template>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="guardando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { getCurrentInstance, ref } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { iso } from '../../composables/fechas'
import { useCatalogosEmocional } from '../../composables/emocional'

// Crear o editar una alerta de bienestar. abrir(alerta?, { estudiante_id, nombre }?)
const emit = defineEmits(['guardada'])
const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()

const abierto = ref(false)
const guardando = ref(false)
const form = ref({})
const nombre = ref('')
const estudiantes = ref([])

const CAMPOS = ['id', 'estudiante_id', 'tipo', 'intensidad', 'estado', 'fase', 'fecha', 'proxima_revision', 'responsable_id', 'equipo', 'descripcion', 'lugar', 'familia_contactada', 'derivacion', 'fecha_cierre', 'motivo_cierre']

function abrir (alerta = null, estudiante = null) {
  if (alerta) {
    form.value = Object.fromEntries(CAMPOS.map(c => [c, alerta[c] ?? (c === 'familia_contactada' ? false : '')]))
    nombre.value = alerta.estudiante?.nombre_completo || ''
  } else {
    form.value = {
      estudiante_id: estudiante?.estudiante_id ?? null, tipo: 'bienestar', intensidad: 'baja', estado: 'activa', fase: 'contencion',
      fecha: iso(new Date()), proxima_revision: '', responsable_id: null, equipo: '', descripcion: '', lugar: '',
      familia_contactada: false, derivacion: '', fecha_cierre: '', motivo_cierre: '',
    }
    estudiantes.value = estudiante ? [{ id: estudiante.estudiante_id, nombre: estudiante.nombre, curso: estudiante.curso || '' }] : []
  }
  abierto.value = true
}

function buscar (texto, update) {
  proxy.$axios.get('emocional/protocolo/estudiantes', { params: { q: texto || undefined } })
    .then(res => update(() => { estudiantes.value = res.data }))
    .catch(() => update(() => { estudiantes.value = [] }))
}

async function guardar () {
  guardando.value = true
  const datos = Object.fromEntries(Object.entries(form.value).map(([k, v]) => [k, v === '' ? null : v]))
  try {
    const { data } = datos.id
      ? await proxy.$axios.put(`emocional/alertas/${datos.id}`, datos)
      : await proxy.$axios.post('emocional/alertas', datos)
    proxy.$alert.success(datos.id ? 'Alerta actualizada' : 'Alerta registrada')
    abierto.value = false
    emit('guardada', data)
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

defineExpose({ abrir })
</script>

<style scoped>
.alertaCard { width: 680px; max-width: 96vw }
</style>
