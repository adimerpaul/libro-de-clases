<template>
  <q-dialog v-model="abierto" persistent>
    <q-card class="formCard pieForm">
      <q-form @submit.prevent="guardar">
        <q-card-section class="row items-center no-wrap">
          <div class="col">
            <div class="formTitle">{{ caso ? 'Editar caso PIE' : 'Ingresar estudiante al PIE' }}</div>
            <div class="subtle">
              {{ caso ? `${caso.estudiante.nombre_completo} · ${caso.anio}` : `Año escolar ${anio} · solo estudiantes con matrícula activa y sin caso este año` }}
            </div>
          </div>
          <q-btn flat round dense icon="fas fa-xmark" v-close-popup />
        </q-card-section>

        <q-card-section class="formGrid scroll cuerpo">
          <!-- Estudiante -->
          <q-select
            v-if="!caso" v-model="form.estudiante_id" :options="candidatos" option-value="id" emit-value map-options
            :option-label="e => e.nombre_completo" use-input input-debounce="300" outlined dense class="full"
            label="Estudiante" :loading="buscando" :rules="[v => !!v || 'Elija un estudiante']" hide-bottom-space
            @filter="buscar"
          >
            <template #prepend><q-icon name="fas fa-user-graduate" size="14px" /></template>
            <template #option="{ itemProps, opt }">
              <q-item v-bind="itemProps">
                <q-item-section>
                  <q-item-label>{{ opt.nombre_completo }}</q-item-label>
                  <q-item-label caption>{{ formatoRun(opt.run) }} · {{ opt.curso?.nombre }}</q-item-label>
                </q-item-section>
              </q-item>
            </template>
            <template #no-option><q-item><q-item-section class="text-grey">Sin estudiantes disponibles</q-item-section></q-item></template>
          </q-select>

          <div class="full seccion">Diagnóstico (Decreto 170)</div>
          <q-select
            v-model="form.diagnostico" :options="cat.diagnosticos" option-value="label" option-label="label" emit-value map-options
            outlined dense class="full" label="Diagnóstico" :rules="[v => !!v || 'Requerido']" hide-bottom-space
          >
            <template #option="{ itemProps, opt }">
              <q-item v-bind="itemProps">
                <q-item-section>{{ opt.label }}</q-item-section>
                <q-item-section side><span class="badge" :class="TIPOS_NEE[opt.tipo].badge">{{ TIPOS_NEE[opt.tipo].label }}</span></q-item-section>
              </q-item>
            </template>
            <template v-if="tipoNee" #append><span class="badge" :class="TIPOS_NEE[tipoNee].badge">{{ TIPOS_NEE[tipoNee].label }}</span></template>
          </q-select>
          <q-input v-model="form.diagnostico_detalle" outlined dense class="full" label="Detalle del diagnóstico" hint="Opcional: especificación, comorbilidad, grado" />
          <q-select v-model="form.estado" :options="cat.estados" emit-value map-options outlined dense label="Estado del caso" />
          <q-input v-model="form.fecha_ingreso" type="date" stack-label outlined dense label="Fecha de ingreso al programa" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
          <q-input v-model="form.fecha_evaluacion" type="date" stack-label outlined dense label="Fecha de evaluación diagnóstica" />
          <q-input v-model="form.fecha_reevaluacion" type="date" stack-label outlined dense label="Próxima reevaluación" />

          <template v-if="form.estado === 'egresado'">
            <q-input v-model="form.fecha_egreso" type="date" stack-label outlined dense label="Fecha de egreso" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.motivo_egreso" outlined dense label="Motivo del egreso / alta" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
          </template>

          <div class="full seccion">Equipo multiprofesional</div>
          <div v-for="(p, i) in form.equipo" :key="i" class="full row q-col-gutter-sm items-center no-wrap">
            <div class="col-5"><q-select v-model="p.especialidad" :options="cat.especialidades" outlined dense label="Especialidad" /></div>
            <div class="col"><q-input v-model="p.nombre" outlined dense label="Nombre del profesional" /></div>
            <div class="col-auto"><q-btn flat round dense icon="far fa-trash-can" color="negative" size="sm" @click="form.equipo.splice(i, 1)" /></div>
          </div>
          <div class="full">
            <q-btn flat dense no-caps icon="fas fa-plus" label="Agregar profesional" color="primary" size="sm"
              @click="form.equipo.push({ especialidad: cat.especialidades[0], nombre: '' })" />
          </div>

          <div class="full seccion">Plan de apoyo</div>
          <div class="full apoyos">
            <q-checkbox v-for="a in cat.apoyos" :key="a" v-model="form.apoyos" :val="a" :label="a" dense />
          </div>
          <q-select
            v-model="form.responsable_id" :options="cat.responsables" option-value="id" option-label="name" emit-value map-options clearable
            outlined dense label="Profesional responsable"
          />
          <q-input v-model.number="form.horas_semanales" type="number" min="0" max="44" outlined dense label="Horas de apoyo semanales" />

          <div class="full seccion">Perfil del estudiante</div>
          <q-input v-model="form.fortalezas" type="textarea" autogrow outlined dense class="full" label="Fortalezas e intereses" />
          <q-input v-model="form.barreras" type="textarea" autogrow outlined dense class="full" label="Barreras para el aprendizaje y la participación" />
          <q-input v-model="form.necesidades_apoyo" type="textarea" autogrow outlined dense class="full" label="Necesidades de apoyo y adecuaciones" />
          <q-input v-model="form.acuerdos_familia" type="textarea" autogrow outlined dense class="full" label="Acuerdos con la familia" />
          <q-input v-model="form.observaciones" type="textarea" autogrow outlined dense class="full" label="Observaciones" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn type="submit" no-caps unelevated :label="caso ? 'Guardar cambios' : 'Ingresar al PIE'" class="btnPrimary" :loading="guardando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { mensajeError } from '../composables/useCrud'
import { formatoRun } from '../composables/formato'
import { TIPOS_NEE, useCatalogosPie } from '../composables/pie'

// Diálogo para ingresar (caso = null) o editar un caso PIE. Emite "guardado" con el caso completo.
const props = defineProps({
  caso: { type: Object, default: null },
  anio: { type: Number, required: true },
})
const emit = defineEmits(['guardado'])
const abierto = defineModel({ type: Boolean, default: false })

const { proxy } = getCurrentInstance()
const cat = useCatalogosPie(proxy)

const CAMPOS = [
  'estado', 'diagnostico', 'diagnostico_detalle', 'fecha_ingreso', 'fecha_evaluacion', 'fecha_reevaluacion',
  'responsable_id', 'horas_semanales', 'fortalezas', 'barreras', 'necesidades_apoyo', 'acuerdos_familia',
  'observaciones', 'fecha_egreso', 'motivo_egreso',
]

const vacio = () => ({
  estudiante_id: null, estado: 'en_evaluacion', diagnostico: null, diagnostico_detalle: '',
  fecha_ingreso: new Date().toISOString().slice(0, 10), fecha_evaluacion: '', fecha_reevaluacion: '',
  responsable_id: proxy.$auth.hasPermission('pie.editar') ? proxy.$auth.user?.id : null,
  equipo: [], apoyos: [], horas_semanales: null, fortalezas: '', barreras: '', necesidades_apoyo: '',
  acuerdos_familia: '', observaciones: '', fecha_egreso: '', motivo_egreso: '',
})

const form = ref(vacio())
const guardando = ref(false)

watch(abierto, v => {
  if (!v) return
  form.value = props.caso
    ? {
        ...Object.fromEntries(CAMPOS.map(c => [c, props.caso[c] ?? ''])),
        equipo: (props.caso.equipo || []).map(p => ({ ...p })),
        apoyos: [...(props.caso.apoyos || [])],
      }
    : vacio()
})

const tipoNee = computed(() => cat.value.diagnosticos.find(d => d.label === form.value.diagnostico)?.tipo)

// ── Búsqueda de estudiantes candidatos ──
const candidatos = ref([])
const buscando = ref(false)

function buscar (texto, update) {
  buscando.value = true
  proxy.$axios.get('pie/estudiantes', { params: { anio: props.anio, q: texto || undefined } })
    .then(res => update(() => { candidatos.value = res.data }))
    .catch(err => { update(() => {}); proxy.$alert.error(mensajeError(err)) })
    .finally(() => { buscando.value = false })
}

function guardar () {
  // Vacíos → null; el equipo solo con nombre
  const datos = Object.fromEntries(Object.entries(form.value).map(([k, v]) => [k, v === '' ? null : v]))
  datos.equipo = form.value.equipo.filter(p => p.nombre?.trim())
  if (!props.caso) datos.anio = props.anio
  else delete datos.estudiante_id

  guardando.value = true
  const req = props.caso
    ? proxy.$axios.put(`pie/casos/${props.caso.id}`, datos)
    : proxy.$axios.post('pie/casos', datos)

  req.then(res => {
    proxy.$alert.success(props.caso ? 'Caso PIE actualizado' : 'Estudiante ingresado al PIE', res.data.estudiante?.nombre_completo)
    abierto.value = false
    emit('guardado', res.data)
  })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { guardando.value = false })
}
</script>

<style scoped>
.pieForm { width: 780px }
.cuerpo { max-height: 68vh }
.seccion { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #6d8090; border-top: 1px solid #e3eaf1; padding-top: 10px }
.apoyos { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 6px 12px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
</style>
