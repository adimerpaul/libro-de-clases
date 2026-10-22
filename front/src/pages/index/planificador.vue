<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Planificador pedagógico</h1>
        <p>Planifica cada clase desde el OA y reutiliza ese mismo contexto en tus evaluaciones. Solo ves tus cursos y asignaturas.</p>
      </div>
      <q-btn label="+ Nueva planificación" no-caps unelevated class="btnPrimary" :disable="!carga.length" @click="nueva()" />
    </div>

    <div class="card semanaBar">
      <div class="semanaNav">
        <q-btn flat round dense icon="fas fa-chevron-left" aria-label="Semana anterior" @click="moverSemana(-1)" />
        <div class="semanaTxt">
          <b>Semana del {{ rangoTexto }}</b>
          <small>{{ planes.length }} {{ planes.length === 1 ? 'clase planificada' : 'clases planificadas' }}</small>
        </div>
        <q-btn flat round dense icon="fas fa-chevron-right" aria-label="Semana siguiente" @click="moverSemana(1)" />
        <q-btn flat dense no-caps label="Hoy" class="q-ml-sm" @click="irAHoy" />
      </div>
      <q-select
        v-model="filtroAsignacion" :options="opcionesCarga" emit-value map-options clearable dense outlined
        label="Curso y asignatura" style="min-width: 260px"
      />
    </div>

    <div v-if="!cargando && !carga.length" class="card vacio">
      <q-icon name="far fa-calendar-xmark" size="32px" />
      <b>No tienes asignaturas asignadas este año</b>
      <span class="subtle">Pide a Dirección o UTP que te asigne cursos y asignaturas en Administrador → Asignación docente.</span>
    </div>

    <div v-else class="semana">
      <div v-for="dia in dias" :key="dia.fecha" class="dia" :class="{ hoy: dia.fecha === hoyIso }">
        <div class="diaHead">
          <div>
            <b>{{ dia.nombre }}</b>
            <small>{{ dia.fechaTxt }}</small>
          </div>
          <q-btn flat round dense size="sm" icon="fas fa-plus" :disable="!carga.length" @click="nueva(dia.fecha)">
            <q-tooltip>Planificar este día</q-tooltip>
          </q-btn>
        </div>

        <div
          v-for="p in planesDe(dia.fecha)" :key="p.id" class="plan" :style="{ borderLeftColor: p.asignacion.asignatura.color || '#0f4f85' }"
          @click="editar(p)"
        >
          <div class="planTop">
            <span class="planHora">{{ p.hora_inicio ? p.hora_inicio.slice(0, 5) : 'Sin hora' }} · {{ p.asignacion.curso.nombre }}</span>
            <span class="estado" :class="p.estado">{{ estadoTxt[p.estado] }}</span>
          </div>
          <b class="planTitulo">{{ p.titulo }}</b>
          <small>{{ p.asignacion.asignatura.nombre }}</small>
          <small v-if="p.objetivo" class="planOa"><q-icon name="fas fa-bullseye" size="10px" /> {{ p.objetivo.codigo }}</small>
          <small v-if="p.evidencia" class="subtle"><b>Evidencia:</b> {{ p.evidencia }}</small>
        </div>

        <div v-if="!planesDe(dia.fecha).length" class="sinClases">Sin clases planificadas</div>
      </div>
    </div>

    <q-dialog v-model="dialog" :maximized="$q.screen.lt.sm">
      <q-card class="planCard">
        <q-form @submit.prevent="guardar">
          <q-card-section class="row items-center no-wrap">
            <div class="col">
              <div class="formTitle">{{ form.id ? 'Editar planificación' : 'Nueva planificación' }}</div>
              <div class="subtle">Inicio · desarrollo · cierre, vinculados a un OA del catálogo curricular.</div>
            </div>
            <q-btn flat round dense icon="fas fa-xmark" v-close-popup />
          </q-card-section>

          <q-card-section class="formGrid4">
            <q-select
              v-model="form.asignacion_id" :options="opcionesCarga" emit-value map-options label="Curso y asignatura"
              outlined dense class="span2" :rules="[v => !!v || 'Requerido']" hide-bottom-space
              @update:model-value="form.objetivo_aprendizaje_id = null"
            />
            <q-input v-model="form.fecha" type="date" label="Fecha" outlined dense stack-label :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <div class="row no-wrap q-gutter-x-sm">
              <q-input v-model="form.hora_inicio" type="time" label="Hora" outlined dense stack-label class="col" />
              <q-input v-model.number="form.duracion_minutos" type="number" label="Min" outlined dense class="col" />
            </div>

            <q-select
              v-model="form.objetivo_aprendizaje_id" :options="oas" option-value="id" :option-label="o => `${o.codigo} · ${o.unidad || ''}`"
              emit-value map-options clearable label="Objetivo de aprendizaje (OA)" outlined dense class="span4"
              :disable="!form.asignacion_id" :loading="cargandoOa"
              :hint="form.asignacion_id && !oas.length && !cargandoOa ? 'No hay OA cargados para este nivel y asignatura' : ''"
            >
              <template #option="{ opt, itemProps }">
                <q-item v-bind="itemProps">
                  <q-item-section>
                    <q-item-label><b>{{ opt.codigo }}</b> · {{ opt.unidad }}</q-item-label>
                    <q-item-label caption lines="2">{{ opt.texto }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <div v-if="oaSel" class="oaBox span4">
              <div class="row items-center q-gutter-x-sm">
                <b>{{ oaSel.codigo }}</b><span class="subtle">{{ oaSel.unidad }}</span>
                <span v-if="oaSel.es_demo" class="badge red">OA demo · no oficial</span>
              </div>
              <div>{{ oaSel.texto }}</div>
              <div v-if="oaSel.habilidades?.length" class="q-mt-xs">
                <span v-for="h in oaSel.habilidades" :key="h" class="badge q-mr-xs">{{ h }}</span>
              </div>
            </div>

            <q-input v-model="form.titulo" label="Título o foco de la clase" outlined dense class="span4" :rules="[v => !!v || 'Requerido']" hide-bottom-space />

            <q-input v-model="form.inicio" type="textarea" autogrow label="Inicio" outlined dense class="span4 momento" hint="Activación de conocimientos previos, propósito de la clase" />
            <q-input v-model="form.desarrollo" type="textarea" autogrow label="Desarrollo" outlined dense class="span4 momento" hint="Modelado, práctica guiada, trabajo colaborativo" />
            <q-input v-model="form.cierre" type="textarea" autogrow label="Cierre" outlined dense class="span4 momento" hint="Síntesis, ticket de salida, metacognición" />

            <q-input v-model="form.evidencia" label="Evidencia de aprendizaje" outlined dense class="span2" placeholder="Ticket de salida, guía, organizador…" />
            <q-input v-model="form.recursos" label="Recursos" outlined dense class="span2" placeholder="Guía, presentación, material concreto…" />
            <q-input v-model="form.adecuaciones" type="textarea" autogrow label="Adecuaciones y apoyos (DUA / PIE)" outlined dense class="span4" />

            <div class="span4">
              <div class="fieldLabel">Estado</div>
              <q-btn-toggle
                v-model="form.estado" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
                :options="[{ label: 'Borrador', value: 'borrador' }, { label: 'Planificada', value: 'planificada' }, { label: 'Realizada', value: 'realizada' }]"
              />
            </div>
          </q-card-section>

          <q-card-actions class="q-px-md q-pb-md">
            <template v-if="form.id">
              <q-btn flat no-caps color="negative" icon="far fa-trash-can" label="Eliminar" @click="eliminar" />
              <q-btn flat no-caps icon="far fa-copy" label="Copiar a la semana siguiente" @click="copiarSiguiente" />
            </template>
            <q-space />
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="guardando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { iso, lunesDe, sumarDias } from '../../composables/fechas'

definePage({ meta: { title: 'Planificador', permiso: 'clases.dictar' } })

const { proxy } = getCurrentInstance()

const estadoTxt = { borrador: 'Borrador', planificada: 'Planificada', realizada: 'Realizada' }
const NOMBRES = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes']

const hoyIso = iso(new Date())
const lunes = ref(lunesDe(new Date()))
const carga = ref([])
const planes = ref([])
const cargando = ref(true)
const filtroAsignacion = ref(null)

const dias = computed(() => NOMBRES.map((nombre, i) => {
  const d = sumarDias(lunes.value, i)
  return { nombre, fecha: iso(d), fechaTxt: d.toLocaleDateString('es-CL', { day: 'numeric', month: 'short' }) }
}))

const rangoTexto = computed(() => {
  const fmt = d => d.toLocaleDateString('es-CL', { day: 'numeric', month: 'long' })
  return `${fmt(lunes.value)} al ${fmt(sumarDias(lunes.value, 4))}`
})

const opcionesCarga = computed(() => carga.value.map(a => ({
  label: `${a.curso.nombre} · ${a.asignatura.nombre}`,
  value: a.id,
})))

function planesDe (fecha) {
  return planes.value
    .filter(p => p.fecha === fecha && (!filtroAsignacion.value || p.asignacion_id === filtroAsignacion.value))
}

function cargarSemana () {
  return proxy.$axios.get('docente/planificaciones', {
    params: { desde: iso(lunes.value), hasta: iso(sumarDias(lunes.value, 6)) },
  })
    .then(res => { planes.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
}

function moverSemana (n) {
  lunes.value = sumarDias(lunes.value, 7 * n)
  cargarSemana()
}

function irAHoy () {
  lunes.value = lunesDe(new Date())
  cargarSemana()
}

// ── Formulario ──
const dialog = ref(false)
const guardando = ref(false)
const form = ref({})
const oas = ref([])
const cargandoOa = ref(false)

const vacio = fecha => ({
  asignacion_id: filtroAsignacion.value || (carga.value.length === 1 ? carga.value[0].id : null),
  objetivo_aprendizaje_id: null,
  fecha: fecha || hoyIso,
  hora_inicio: '08:00',
  duracion_minutos: 90,
  titulo: '',
  inicio: '',
  desarrollo: '',
  cierre: '',
  evidencia: '',
  recursos: '',
  adecuaciones: '',
  estado: 'planificada',
})

function nueva (fecha) {
  form.value = vacio(fecha)
  dialog.value = true
}

function editar (p) {
  form.value = {
    ...p,
    hora_inicio: p.hora_inicio ? p.hora_inicio.slice(0, 5) : null,
  }
  dialog.value = true
}

const oaSel = computed(() => oas.value.find(o => o.id === form.value.objetivo_aprendizaje_id))

// OA del nivel del curso y de la asignatura elegidos
watch(() => form.value.asignacion_id, id => {
  oas.value = []
  const a = carga.value.find(x => x.id === id)
  if (!a) return
  cargandoOa.value = true
  proxy.$axios.get('docente/oa', { params: { nivel: a.curso.nivel, asignatura_id: a.asignatura_id } })
    .then(res => { oas.value = res.data })
    .finally(() => { cargandoOa.value = false })
})

function payload () {
  const f = form.value
  return {
    asignacion_id: f.asignacion_id,
    objetivo_aprendizaje_id: f.objetivo_aprendizaje_id,
    fecha: f.fecha,
    hora_inicio: f.hora_inicio || null,
    duracion_minutos: f.duracion_minutos || null,
    titulo: f.titulo,
    inicio: f.inicio,
    desarrollo: f.desarrollo,
    cierre: f.cierre,
    evidencia: f.evidencia,
    recursos: f.recursos,
    adecuaciones: f.adecuaciones,
    estado: f.estado,
  }
}

function guardar () {
  guardando.value = true
  const req = form.value.id
    ? proxy.$axios.put(`docente/planificaciones/${form.value.id}`, payload())
    : proxy.$axios.post('docente/planificaciones', payload())
  req
    .then(() => {
      proxy.$alert.success(form.value.id ? 'Planificación actualizada' : 'Planificación creada')
      dialog.value = false
      cargarSemana()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { guardando.value = false })
}

function eliminar () {
  proxy.$alert.confirm(`¿Eliminar la planificación "${form.value.titulo}"?`).onOk(() => {
    proxy.$axios.delete(`docente/planificaciones/${form.value.id}`)
      .then(() => {
        proxy.$alert.success('Planificación eliminada')
        dialog.value = false
        cargarSemana()
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

function copiarSiguiente () {
  const datos = { ...payload(), fecha: iso(sumarDias(new Date(form.value.fecha + 'T12:00:00'), 7)), estado: 'borrador' }
  proxy.$axios.post('docente/planificaciones', datos)
    .then(() => proxy.$alert.success('Copiada como borrador', `Para el ${datos.fecha.split('-').reverse().join('-')}`))
    .catch(err => proxy.$alert.error(mensajeError(err)))
}

onMounted(() => {
  proxy.$axios.get('docente/asignaciones')
    .then(res => { carga.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cargando.value = false })
  cargarSemana()
})
</script>

<style scoped>
.semanaBar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 15px; padding: 10px 14px }
.semanaNav { display: flex; align-items: center; gap: 6px }
.semanaTxt b { display: block; font-size: 15px }
.semanaTxt small { color: #6d8090 }

.semana { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px }
.dia { background: #fff; border: 1px solid #d9e3ec; border-radius: 18px; padding: 12px; min-height: 260px; box-shadow: 0 7px 22px rgba(22, 52, 80, .055) }
.dia.hoy { border-color: #0f4f85; box-shadow: 0 0 0 2px #0f4f8522 }
.diaHead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px }
.diaHead b { display: block; font-size: 14px }
.diaHead small { color: #6d8090; font-size: 11px }

.plan {
  display: flex; flex-direction: column; gap: 3px;
  border: 1px solid #e3eaf1; border-left: 4px solid #0f4f85; border-radius: 12px;
  padding: 9px 10px; margin-bottom: 8px; background: #fbfdff; cursor: pointer;
  transition: box-shadow .15s, transform .15s;
}
.plan:hover { box-shadow: 0 6px 16px rgba(22, 52, 80, .1); transform: translateY(-1px) }
.planTop { display: flex; justify-content: space-between; align-items: center; gap: 6px }
.planHora { font-size: 11px; font-weight: 800; color: #486477 }
.planTitulo { font-size: 13px; line-height: 1.3 }
.plan small { font-size: 11px; color: #486477; line-height: 1.35 }
.planOa { color: #6b4fbb !important; font-weight: 700 }
.estado { font-size: 9px; font-weight: 800; border-radius: 999px; padding: 2px 7px; text-transform: uppercase; letter-spacing: .04em }
.estado.borrador { background: #fff5e6; color: #a35f00 }
.estado.planificada { background: #eaf3fb; color: #0f4f85 }
.estado.realizada { background: #eaf8f1; color: #17703f }
.sinClases { font-size: 12px; color: #9aabb8; text-align: center; padding: 18px 0 }

.vacio { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 40px; text-align: center; color: #486477 }

.planCard { width: 820px; max-width: 96vw; border-radius: 18px }
.formGrid4 { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)) }
.span2 { grid-column: span 2 }
.span4 { grid-column: 1 / -1 }
.oaBox { background: #f6f3ff; border: 1px solid #e2dafb; border-radius: 12px; padding: 10px 12px; font-size: 13px; line-height: 1.45 }
.fieldLabel { font-size: 12px; font-weight: 700; color: #486477; margin-bottom: 4px }

@media (max-width: 1100px) { .semana { grid-template-columns: repeat(2, minmax(0, 1fr)) } }
@media (max-width: 720px) {
  .semana { grid-template-columns: 1fr }
  .dia { min-height: 0 }
  .formGrid4 { grid-template-columns: 1fr 1fr }
}
</style>
