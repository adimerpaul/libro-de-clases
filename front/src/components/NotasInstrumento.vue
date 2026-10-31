<template>
  <q-dialog v-model="abierto" persistent>
    <q-card class="formCard notasCard">
      <q-card-section class="row items-start no-wrap">
        <div class="col">
          <div class="formTitle">Notas · {{ datos?.instrumento.nombre || '…' }}</div>
          <div v-if="datos" class="subtle">
            {{ datos.instrumento.curso }} · {{ datos.instrumento.asignatura }} ·
            {{ datos.puntaje_total }} pts · exigencia {{ datos.instrumento.exigencia }}%
            <template v-if="datos.puntaje_aprobacion != null"> · 4,0 con {{ formato(datos.puntaje_aprobacion) }} pts</template>
          </div>
        </div>
        <div v-if="datos?.promedio != null" class="promedio">
          <small>Promedio curso</small>
          <b :class="clase(datos.promedio)">{{ formato(datos.promedio) }}</b>
        </div>
      </q-card-section>

      <q-card-section class="q-pt-none tablaWrap">
        <q-inner-loading :showing="cargando" />
        <div v-if="datos && !datos.puntaje_total" class="aviso">
          El instrumento no tiene preguntas con puntaje. Agréguelas en el editor antes de registrar notas.
        </div>
        <q-markup-table v-else flat dense separator="horizontal" class="tabla">
          <thead>
            <tr><th class="text-left">N°</th><th class="text-left">Estudiante</th><th>Puntaje</th><th>Nota</th><th class="text-left">Observación</th></tr>
          </thead>
          <tbody>
            <tr v-for="e in filas" :key="e.id">
              <td class="subtle">{{ e.numero_lista }}</td>
              <td>{{ e.nombre }}</td>
              <td class="celdaPuntaje">
                <q-input
                  v-model="e.puntaje" type="number" step="0.5" min="0" :max="datos.puntaje_total" dense outlined
                  input-class="text-center" :placeholder="'—'" @update:model-value="e.cambiado = true"
                />
              </td>
              <td class="text-center">
                <b v-if="!e.cambiado" :class="clase(e.nota)">{{ formato(e.nota) }}</b>
                <span v-else class="subtle">al guardar</span>
              </td>
              <td><q-input v-model="e.observacion" dense outlined maxlength="300" @update:model-value="e.cambiado = true" /></td>
            </tr>
          </tbody>
        </q-markup-table>
        <div v-if="datos && datos.puntaje_total && !filas.length" class="subtle q-pa-md">El curso no tiene estudiantes con matrícula activa.</div>
      </q-card-section>

      <q-card-actions align="right">
        <span class="subtle q-mr-auto q-ml-sm">La nota la calcula el sistema con la escala 1,0–7,0. Deje el puntaje vacío para quitar la nota. Las ven los apoderados.</span>
        <q-btn flat no-caps label="Cerrar" @click="cerrar" />
        <q-btn no-caps unelevated class="btnPrimary" icon="fas fa-floppy-disk" label="Guardar notas" :loading="guardando" :disable="!cambios" @click="guardar" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
import { useQuasar } from 'quasar'
import { mensajeError } from '../composables/useCrud'

// Planilla de notas de un instrumento propio. Se abre con abrir(id).
const { proxy } = getCurrentInstance()
const $q = useQuasar()

const abierto = ref(false)
const instrumentoId = ref(null)
const datos = ref(null)
const filas = ref([])
const cargando = ref(false)
const guardando = ref(false)

const cambios = computed(() => filas.value.some(f => f.cambiado))
const formato = n => (n == null || n === '' ? '—' : Number(n).toFixed(1).replace('.', ','))
const clase = n => (n == null ? '' : n < 4 ? 'roja' : 'azul')

function cargarDatos (data) {
  datos.value = data
  filas.value = data.estudiantes.map(e => ({ ...e, puntaje: e.puntaje ?? '', observacion: e.observacion ?? '', cambiado: false }))
}

async function abrir (id) {
  instrumentoId.value = id
  datos.value = null
  filas.value = []
  abierto.value = true
  cargando.value = true
  try {
    cargarDatos((await proxy.$axios.get(`docente/instrumentos/${id}/notas`)).data)
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
    abierto.value = false
  } finally {
    cargando.value = false
  }
}

async function guardar () {
  guardando.value = true
  try {
    const notas = filas.value.filter(f => f.cambiado).map(f => ({
      estudiante_id: f.id,
      puntaje: f.puntaje === '' || f.puntaje == null ? null : Number(f.puntaje),
      observacion: f.observacion || null,
    }))
    cargarDatos((await proxy.$axios.put(`docente/instrumentos/${instrumentoId.value}/notas`, { notas })).data)
    proxy.$alert.success('Notas guardadas')
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

function cerrar () {
  if (!cambios.value) {
    abierto.value = false
    return
  }
  $q.dialog({ title: 'Cambios sin guardar', message: '¿Cerrar sin guardar las notas?', cancel: { label: 'Seguir editando', flat: true, noCaps: true }, ok: { label: 'Cerrar', color: 'negative', noCaps: true } })
    .onOk(() => { abierto.value = false })
}

defineExpose({ abrir })
</script>

<style scoped>
.notasCard { width: 860px; max-width: 96vw }
.tablaWrap { max-height: 62vh; overflow: auto; position: relative }
.tabla th { font-size: 11px; color: #6d8090; text-transform: uppercase; letter-spacing: .05em }
.celdaPuntaje { width: 110px }
.promedio { text-align: right }
.promedio small { display: block; font-size: 11px; color: #6d8090 }
.promedio b { font-size: 24px }
.azul { color: #0f4f85 }
.roja { color: #bd1f2d }
.aviso { padding: 12px; border: 1px dashed #f2d9b5; background: #fff8ec; border-radius: 12px; color: #9a5a0c }
</style>
