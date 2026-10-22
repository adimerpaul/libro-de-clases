<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Matrículas</h1>
        <p>Nómina de cada curso por año escolar: matricula estudiantes, cámbialos de curso, registra retiros y traslados, y numera la lista.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn
          v-if="$auth.hasPermission('matriculas.crear')" flat no-caps icon="far fa-copy" label="Copiar nómina de otro curso"
          :disable="!cursoId" @click="abrirCopiar"
        />
        <q-btn
          v-if="$auth.hasPermission('matriculas.crear')" label="+ Matricular estudiante" no-caps unelevated class="btnPrimary"
          :disable="!cursoId" @click="abrirMatricular"
        />
      </div>
    </div>

    <div class="card">
      <div class="cardHead">
        <div class="filtros">
          <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="width: 130px" @update:model-value="cambiarAnio" />
          <q-select
            v-model="cursoId" :options="cursos" option-value="id" option-label="nombre" emit-value map-options
            dense outlined label="Curso" style="min-width: 170px" @update:model-value="seleccionarCurso"
          />
        </div>
        <div v-if="cursoSel" class="resumen">
          <span class="badge blue">{{ activas }} activos</span>
          <span v-if="rows.length - activas" class="badge red">{{ rows.length - activas }} retirados / trasladados</span>
          <span class="badge" :class="cursoSel.profesor_jefe ? 'ok' : ''">Jefatura: {{ cursoSel.profesor_jefe?.name || 'sin asignar' }}</span>
          <q-btn
            v-if="$auth.hasPermission('matriculas.editar') && rows.length" flat dense no-caps size="sm" icon="fas fa-arrow-down-a-z"
            label="Numerar A–Z" @click="renumerar"
          >
            <q-tooltip>Numera la lista en orden alfabético (activos primero)</q-tooltip>
          </q-btn>
        </div>
      </div>

      <div v-if="!cursos.length && !loading" class="vacio">
        No hay cursos en {{ anio }}. Créalos (o cópialos del año anterior) en la pestaña Cursos.
      </div>

      <q-table
        v-else :rows="rows" :columns="columns" row-key="id" flat :loading="loading"
        :pagination="{ rowsPerPage: 0 }" hide-pagination class="adminTable" no-data-label="El curso aún no tiene estudiantes matriculados"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Cambiar de curso / editar', icon: 'fas fa-right-left', permiso: 'matriculas.editar', click: () => editarMatricula(p.row) },
              { label: 'Retirar o trasladar', icon: 'fas fa-door-open', permiso: 'matriculas.editar', si: p.row.estado === 'activa', click: () => editarMatricula(p.row, 'retirada') },
              { label: 'Reactivar matrícula', icon: 'fas fa-rotate-left', permiso: 'matriculas.editar', si: p.row.estado !== 'activa', click: () => editarMatricula(p.row, 'activa') },
              { label: 'Eliminar matrícula', icon: 'far fa-trash-can', peligro: true, permiso: 'matriculas.eliminar', click: () => eliminar(p.row, `¿Eliminar la matrícula de ${p.row.estudiante.nombre_completo}? (La ficha del estudiante se conserva.)`) },
            ]" />
          </q-td>
        </template>
        <template #body-cell-estudiante="p">
          <q-td :props="p" :class="{ inactivo: p.row.estado !== 'activa' }">
            <b>{{ p.row.estudiante.nombre_completo }}</b>
            <span v-if="p.row.estudiante.pie" class="badge violeta q-ml-xs">PIE</span>
            <div class="subtle">{{ formatoRun(p.row.estudiante.run) }}</div>
          </q-td>
        </template>
        <template #body-cell-estado="p">
          <q-td :props="p">
            <span class="badge" :class="p.row.estado === 'activa' ? 'ok' : 'red'">{{ estadoTxt[p.row.estado] }}</span>
            <div v-if="p.row.fecha_retiro" class="subtle">{{ fechaCl(p.row.fecha_retiro) }}{{ p.row.motivo_retiro ? ` · ${p.row.motivo_retiro}` : '' }}</div>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- Matricular -->
    <q-dialog v-model="matDialog">
      <q-card class="formCard">
        <q-card-section>
          <div class="formTitle">Matricular en {{ cursoSel?.nombre }} {{ anio }}</div>
          <div class="subtle">Estudiantes con ficha y sin matrícula activa en {{ anio }}. Si no aparece, créalo en la pestaña Estudiantes.</div>
        </q-card-section>
        <q-card-section class="q-pt-none formGrid">
          <q-select
            v-model="matEstudiante" :options="candidatosFiltrados" option-value="id" :option-label="e => `${e.nombre_completo} · ${formatoRun(e.run)}`"
            emit-value map-options use-input input-debounce="200" label="Estudiante" outlined dense class="full"
            :loading="cargandoCandidatos" @filter="filtrarCandidatos"
          >
            <template #no-option><q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item></template>
          </q-select>
          <q-input v-model="matFecha" type="date" label="Fecha de matrícula" stack-label outlined dense class="full" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn no-caps unelevated label="Matricular" class="btnPrimary" :disable="!matEstudiante" :loading="guardando" @click="matricular" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Editar matrícula: cambio de curso, número, retiro / traslado -->
    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section>
            <div class="formTitle">Matrícula {{ anio }}</div>
            <div class="subtle">{{ form.estudiante?.nombre_completo }}</div>
          </q-card-section>
          <q-card-section class="q-pt-none formGrid">
            <q-select
              v-model="form.curso_id" :options="cursos" option-value="id" option-label="nombre" emit-value map-options
              label="Curso" outlined dense hint="Solo cursos del mismo año"
            />
            <q-input v-model.number="form.numero_lista" type="number" label="N° de lista" outlined dense />
            <q-input v-model="form.fecha_matricula" type="date" label="Fecha de matrícula" stack-label outlined dense class="full" />
            <div class="full">
              <div class="fieldLabel">Estado</div>
              <q-btn-toggle
                v-model="form.estado" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
                :options="[{ label: 'Activa', value: 'activa' }, { label: 'Retirada', value: 'retirada' }, { label: 'Trasladada', value: 'trasladada' }]"
              />
            </div>
            <template v-if="form.estado !== 'activa'">
              <q-input v-model="form.fecha_retiro" type="date" label="Fecha de retiro" stack-label outlined dense />
              <q-input v-model="form.motivo_retiro" label="Motivo" outlined dense placeholder="Cambio de domicilio, traslado a…" />
            </template>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Copiar nómina -->
    <q-dialog v-model="copDialog">
      <q-card class="formCard">
        <q-card-section>
          <div class="formTitle">Copiar nómina a {{ cursoSel?.nombre }} {{ anio }}</div>
          <div class="subtle">
            Matricula aquí a los estudiantes activos del curso de origen (p. ej. el curso del año anterior).
            Quien ya tenga matrícula en {{ anio }} se omite. No decide la promoción: ajusta después los casos que corresponda.
          </div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-select
            v-model="copOrigen" :options="cursosOrigen" option-value="id" :option-label="c => `${c.nombre} ${c.anio} · ${c.estudiantes_count} estudiantes`"
            emit-value map-options label="Curso de origen" outlined dense
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn no-caps unelevated label="Copiar nómina" class="btnPrimary" :disable="!copOrigen" :loading="guardando" @click="copiar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCrud, mensajeError } from '../../composables/useCrud'
import { formatoRun } from '../../composables/formato'
import { iso, fechaCl } from '../../composables/fechas'
import MenuAcciones from '../../components/MenuAcciones.vue'

definePage({ meta: { title: 'Matrículas', permiso: 'matriculas.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()

const estadoTxt = { activa: 'Activa', retirada: 'Retirada', trasladada: 'Trasladada' }
const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(Number(route.query.anio) || actual)
const cursoId = ref(route.query.curso ? Number(route.query.curso) : null)
const cursos = ref([])
const guardando = ref(false)

const { rows, loading, dialog, saving, form, cargar, editar, guardar, eliminar } = useCrud(
  'admin/matriculas',
  () => ({}),
  {
    nombre: 'Matrícula',
    params: () => ({ curso_id: cursoId.value }),
    aForm: m => ({
      id: m.id, curso_id: m.curso_id, numero_lista: m.numero_lista, fecha_matricula: m.fecha_matricula,
      estado: m.estado, fecha_retiro: m.fecha_retiro || '', motivo_retiro: m.motivo_retiro || '', estudiante: m.estudiante,
    }),
  },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'numero', label: 'N°', field: 'numero_lista', align: 'center', style: 'width: 50px' },
  { name: 'estudiante', label: 'Estudiante', field: r => r.estudiante.nombre_completo, align: 'left' },
  { name: 'fecha', label: 'Matrícula', field: 'fecha_matricula', align: 'left', format: fechaCl },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
]

const cursoSel = computed(() => cursos.value.find(c => c.id === cursoId.value))
const activas = computed(() => rows.value.filter(m => m.estado === 'activa').length)

function cargarNomina () {
  if (cursoId.value) cargar()
  else rows.value = []
}

function cargarCursos () {
  return proxy.$axios.get('admin/cursos', { params: { anio: anio.value } })
    .then(res => {
      cursos.value = res.data
      if (!cursos.value.some(c => c.id === cursoId.value)) cursoId.value = cursos.value[0]?.id || null
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
}

function seleccionarCurso () {
  router.replace({ query: { anio: anio.value, curso: cursoId.value || undefined } })
  cargarNomina()
}

function cambiarAnio () {
  cargarCursos().then(seleccionarCurso)
}

function editarMatricula (m, estado) {
  editar(m)
  if (estado) {
    form.value.estado = estado
    if (estado !== 'activa' && !form.value.fecha_retiro) form.value.fecha_retiro = iso(new Date())
  }
}

function renumerar () {
  proxy.$alert.confirm(`¿Numerar la lista de ${cursoSel.value.nombre} en orden alfabético? Los retirados y trasladados quedan al final.`).onOk(() => {
    proxy.$axios.post('admin/matriculas/renumerar', { curso_id: cursoId.value })
      .then(res => { proxy.$alert.success(res.data.message); cargar() })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

// ── Matricular ──
const matDialog = ref(false)
const matEstudiante = ref(null)
const matFecha = ref(iso(new Date()))
const candidatos = ref([])
const candidatosFiltrados = ref([])
const cargandoCandidatos = ref(false)

function abrirMatricular () {
  matEstudiante.value = null
  matDialog.value = true
  cargandoCandidatos.value = true
  proxy.$axios.get('admin/estudiantes', { params: { anio: anio.value, sin_matricula: 1 } })
    .then(res => { candidatos.value = res.data; candidatosFiltrados.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cargandoCandidatos.value = false })
}

function filtrarCandidatos (texto, update) {
  update(() => {
    const t = texto.toLowerCase().replace(/[.-]/g, '')
    candidatosFiltrados.value = !t
      ? candidatos.value
      : candidatos.value.filter(e => e.nombre_completo.toLowerCase().includes(t) || e.run.replace('-', '').toLowerCase().includes(t))
  })
}

function matricular () {
  guardando.value = true
  proxy.$axios.post('admin/matriculas', { estudiante_id: matEstudiante.value, curso_id: cursoId.value, fecha_matricula: matFecha.value })
    .then(() => {
      proxy.$alert.success('Estudiante matriculado')
      matDialog.value = false
      cargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { guardando.value = false })
}

// ── Copiar nómina desde otro curso (este año o el anterior) ──
const copDialog = ref(false)
const copOrigen = ref(null)
const cursosOrigen = ref([])

function abrirCopiar () {
  copOrigen.value = null
  copDialog.value = true
  Promise.all([anio.value - 1, anio.value].map(a => proxy.$axios.get('admin/cursos', { params: { anio: a } })))
    .then(([previo, mismo]) => {
      cursosOrigen.value = [...previo.data, ...mismo.data].filter(c => c.id !== cursoId.value && c.estudiantes_count)
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
}

function copiar () {
  guardando.value = true
  proxy.$axios.post('admin/matriculas/copiar', { desde_curso_id: copOrigen.value, hacia_curso_id: cursoId.value })
    .then(res => {
      proxy.$alert.success(res.data.message)
      copDialog.value = false
      cargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { guardando.value = false })
}

onMounted(() => cargarCursos().then(cargarNomina))
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap }
.resumen { display: flex; gap: 6px; flex-wrap: wrap; align-items: center }
.vacio { padding: 30px; text-align: center; color: #6d8090 }
.inactivo b { color: #9aabb8; text-decoration: line-through }
.fieldLabel { font-size: 12px; font-weight: 700; color: #486477; margin-bottom: 4px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
</style>
