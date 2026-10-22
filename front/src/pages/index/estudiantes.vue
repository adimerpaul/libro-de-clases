<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Estudiantes</h1>
        <p>Ficha de cada estudiante y su apoderado. El curso de cada año se asigna con la matrícula; un estudiante conserva su ficha de un año a otro.</p>
      </div>
      <q-btn v-if="$auth.hasPermission('estudiantes.crear')" label="+ Nuevo estudiante" no-caps unelevated class="btnPrimary" @click="nuevo({ curso_id: filtroCurso })" />
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Estudiantes <span class="badge blue q-ml-sm">{{ rows.length }}</span></h3>
        <div class="filtros">
          <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="width: 130px" @update:model-value="cambiarAnio" />
          <q-select
            v-model="filtroCurso" :options="cursos" option-value="id" option-label="nombre" emit-value map-options clearable
            dense outlined label="Curso" style="min-width: 160px" :disable="sinMatricula" @update:model-value="cargar"
          />
          <q-toggle v-model="sinMatricula" dense label="Sin matrícula este año" @update:model-value="cargar" />
          <q-input v-model="filtro" dense outlined debounce="350" placeholder="Nombre o RUN" style="min-width: 200px" @update:model-value="cargar">
            <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
          </q-input>
        </div>
      </div>

      <q-table
        :rows="rows" :columns="columns" row-key="id" flat :loading="loading"
        :pagination="{ rowsPerPage: 25 }" class="adminTable" no-data-label="Sin estudiantes con estos filtros"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Editar ficha', icon: 'fas fa-pen', permiso: 'estudiantes.editar', click: () => editar(p.row) },
              { label: `Matricular en ${anio}`, icon: 'fas fa-user-plus', permiso: 'matriculas.crear', si: !activa(p.row), click: () => abrirMatricular(p.row) },
              { label: 'Ver nómina del curso', icon: 'fas fa-people-roof', permiso: 'matriculas.ver', si: !!p.row.matricula, to: { path: '/matriculas', query: { curso: p.row.matricula?.curso?.id, anio } } },
              { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'estudiantes.eliminar', click: () => eliminar(p.row, `¿Eliminar a ${p.row.nombres} ${p.row.apellido_paterno}? También se eliminan sus matrículas.`) },
            ]" />
          </q-td>
        </template>
        <template #body-cell-nombre="p">
          <q-td :props="p">
            <b>{{ p.row.nombre_completo }}</b>
            <span v-if="p.row.pie" class="badge violeta q-ml-xs">PIE</span>
            <div class="subtle">{{ formatoRun(p.row.run) }}</div>
          </q-td>
        </template>
        <template #body-cell-curso="p">
          <q-td :props="p">
            <template v-if="p.row.matricula">
              <span class="badge" :class="p.row.matricula.estado === 'activa' ? 'blue' : 'red'">
                {{ p.row.matricula.curso.nombre }}{{ p.row.matricula.estado !== 'activa' ? ` · ${p.row.matricula.estado}` : '' }}
              </span>
              <span class="subtle q-ml-xs">N° {{ p.row.matricula.numero_lista }}</span>
            </template>
            <span v-else class="subtle">Sin matrícula {{ anio }}</span>
          </q-td>
        </template>
        <template #body-cell-apoderado="p">
          <q-td :props="p">
            <div>{{ p.row.apoderado_nombre || '—' }}</div>
            <div class="subtle">{{ p.row.apoderado_telefono }}</div>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- Ficha -->
    <q-dialog v-model="dialog">
      <q-card class="formCard fichaCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">{{ form.id ? 'Editar ficha' : 'Nuevo estudiante' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-input v-model="form.run" label="RUN" outlined dense placeholder="12.345.678-5" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.fecha_nacimiento" type="date" label="Fecha de nacimiento" stack-label outlined dense />
            <q-input v-model="form.nombres" label="Nombres" outlined dense class="full" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.apellido_paterno" label="Apellido paterno" outlined dense :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.apellido_materno" label="Apellido materno" outlined dense />
            <q-select v-model="form.sexo" :options="sexos" emit-value map-options clearable label="Sexo" outlined dense />
            <q-toggle v-model="form.pie" label="Programa de Integración (PIE)" />
            <q-input v-model="form.direccion" label="Dirección" outlined dense class="full" />

            <div class="full seccion">Apoderado</div>
            <q-input v-model="form.apoderado_nombre" label="Nombre" outlined dense class="full" />
            <q-input v-model="form.apoderado_run" label="RUN" outlined dense />
            <q-input v-model="form.apoderado_telefono" label="Teléfono" outlined dense placeholder="+56 9 1234 5678" />
            <q-input v-model="form.apoderado_email" label="Correo" type="email" outlined dense class="full" />
            <q-input v-model="form.observaciones" type="textarea" autogrow label="Observaciones" outlined dense class="full" />

            <q-select
              v-if="!form.id && $auth.hasPermission('matriculas.crear')" v-model="form.curso_id" :options="cursos" option-value="id" option-label="nombre"
              emit-value map-options clearable :label="`Matricular en (año ${anio})`" outlined dense class="full"
              hint="Opcional: queda al final de la lista del curso"
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Matricular a un estudiante existente -->
    <q-dialog v-model="matDialog">
      <q-card class="formCard">
        <q-card-section>
          <div class="formTitle">Matricular en {{ anio }}</div>
          <div class="subtle">{{ matEstudiante?.nombre_completo }} · {{ formatoRun(matEstudiante?.run) }}</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-select v-model="matCurso" :options="cursos" option-value="id" option-label="nombre" emit-value map-options label="Curso" outlined dense />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn no-caps unelevated label="Matricular" class="btnPrimary" :disable="!matCurso" :loading="matGuardando" @click="matricular" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue'
import { useRoute } from 'vue-router'
import { useCrud, mensajeError } from '../../composables/useCrud'
import { formatoRun } from '../../composables/formato'
import MenuAcciones from '../../components/MenuAcciones.vue'

definePage({ meta: { title: 'Estudiantes', permiso: 'estudiantes.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()

const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(Number(route.query.anio) || actual)
const filtroCurso = ref(route.query.curso ? Number(route.query.curso) : null)
const sinMatricula = ref(false)
const filtro = ref('')
const cursos = ref([])
const sexos = [{ label: 'Femenino', value: 'F' }, { label: 'Masculino', value: 'M' }, { label: 'Otro / no informa', value: 'X' }]

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/estudiantes',
  () => ({
    run: '', nombres: '', apellido_paterno: '', apellido_materno: '', fecha_nacimiento: '', sexo: null, direccion: '',
    apoderado_nombre: '', apoderado_run: '', apoderado_telefono: '', apoderado_email: '', pie: false, observaciones: '', curso_id: null,
  }),
  {
    nombre: 'Estudiante',
    params: () => ({
      anio: anio.value,
      curso_id: sinMatricula.value ? undefined : filtroCurso.value || undefined,
      sin_matricula: sinMatricula.value ? 1 : undefined,
      q: filtro.value || undefined,
    }),
    aForm: e => ({ ...e, fecha_nacimiento: e.fecha_nacimiento || '', matricula: undefined }),
  },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'nombre', label: 'Estudiante', field: 'nombre_completo', align: 'left', sortable: true },
  { name: 'curso', label: 'Curso', field: r => r.matricula?.curso?.nombre || '', align: 'left', sortable: true },
  { name: 'nacimiento', label: 'Nacimiento', field: 'fecha_nacimiento', align: 'left', format: v => (v ? v.split('-').reverse().join('-') : '—') },
  { name: 'apoderado', label: 'Apoderado', field: 'apoderado_nombre', align: 'left' },
]

const activa = e => e.matricula?.estado === 'activa'

function cargarCursos () {
  return proxy.$axios.get('admin/cursos', { params: { anio: anio.value } })
    .then(res => { cursos.value = res.data })
    .catch(() => {})
}

function cambiarAnio () {
  filtroCurso.value = null
  cargarCursos()
  cargar()
}

// ── Matricular a un estudiante que ya tiene ficha ──
const matDialog = ref(false)
const matEstudiante = ref(null)
const matCurso = ref(null)
const matGuardando = ref(false)

function abrirMatricular (e) {
  matEstudiante.value = e
  matCurso.value = filtroCurso.value
  matDialog.value = true
}

function matricular () {
  matGuardando.value = true
  proxy.$axios.post('admin/matriculas', { estudiante_id: matEstudiante.value.id, curso_id: matCurso.value })
    .then(() => {
      proxy.$alert.success('Estudiante matriculado', matEstudiante.value.nombre_completo)
      matDialog.value = false
      cargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { matGuardando.value = false })
}

onMounted(() => {
  cargarCursos()
  cargar()
})
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap; align-items: center }
.fichaCard { width: 640px }
.seccion { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #6d8090; border-top: 1px solid #e3eaf1; padding-top: 10px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
</style>
