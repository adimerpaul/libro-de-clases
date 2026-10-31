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

      <!-- Filtro por cuenta de apoderado (solo usuarios con el rol Apoderado) -->
      <div class="filtroCuenta">
        <q-icon name="fas fa-user-shield" class="icoCuenta" />
        <q-btn-toggle
          v-model="cuenta" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
          :options="[{ label: 'Todos', value: null }, { label: 'Con cuenta de apoderado', value: 'con' }, { label: 'Sin cuenta', value: 'sin' }]"
          @update:model-value="cambiarCuenta"
        />
        <q-select
          v-model="apoderadoId" :options="cuentasApo" option-value="id" option-label="name" emit-value map-options
          use-input input-debounce="250" clearable dense outlined label="Apoderado (usuario)" class="selApo"
          :disable="cuenta === 'sin'" @filter="buscarApoderados" @update:model-value="cargar"
        >
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="12px" /></template>
          <template #option="{ opt, itemProps }">
            <q-item v-bind="itemProps" dense>
              <q-item-section avatar><div class="apoAvatar">{{ inicialesDe(opt.name) }}</div></q-item-section>
              <q-item-section>
                <q-item-label>{{ opt.name }}</q-item-label>
                <q-item-label caption>{{ opt.email }}<template v-if="opt.run"> · {{ formatoRun(opt.run) }}</template></q-item-label>
              </q-item-section>
              <q-item-section side><span class="badge">{{ opt.estudiantes_count }} est.</span></q-item-section>
            </q-item>
          </template>
          <template #no-option><q-item><q-item-section class="subtle">Ningún usuario con el rol Apoderado coincide.</q-item-section></q-item></template>
        </q-select>
        <span class="subtle">Busca por nombre, correo o RUN · solo usuarios con el rol Apoderado</span>
      </div>

      <q-table
        :rows="rows" :columns="columns" row-key="id" flat :loading="loading"
        :pagination="{ rowsPerPage: 25 }" class="adminTable" no-data-label="Sin estudiantes con estos filtros"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Editar ficha', icon: 'fas fa-pen', permiso: 'estudiantes.editar', click: () => editar(p.row) },
              { label: 'Apoderados (portal familia)', icon: 'fas fa-user-shield', permiso: 'estudiantes.editar', click: () => apoderados.abrir(p.row) },
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
        <template #body-cell-cuenta="p">
          <q-td :props="p">
            <div v-if="p.row.cuentas.length" class="cuentas">
              <button
                v-for="c in p.row.cuentas" :key="c.vinculo_id" class="chipCuenta" :class="{ activo: apoderadoId === c.user.id }"
                @click="filtrarPorApoderado(c.user)"
              >
                <span class="apoAvatar mini">{{ inicialesDe(c.user.name) }}</span>
                <span class="txt"><b>{{ c.user.name }}</b><small>{{ c.parentesco }} · {{ c.user.email }}</small></span>
                <q-tooltip>{{ apoderadoId === c.user.id ? 'Quitar filtro' : `Ver todos los estudiantes de ${c.user.name}` }}</q-tooltip>
              </button>
            </div>
            <q-btn
              v-else-if="$auth.hasPermission('estudiantes.editar')" flat dense no-caps size="sm" color="primary"
              icon="fas fa-link" label="Vincular cuenta" @click="apoderados.abrir(p.row)"
            />
            <span v-else class="subtle">Sin cuenta</span>
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

    <ApoderadosEstudiante ref="apoderados" @cambio="cargar" />

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
import ApoderadosEstudiante from '../../components/ApoderadosEstudiante.vue'

definePage({ meta: { title: 'Estudiantes', permiso: 'estudiantes.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()

const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(Number(route.query.anio) || actual)
const filtroCurso = ref(route.query.curso ? Number(route.query.curso) : null)
const sinMatricula = ref(false)
const apoderados = ref(null)
const filtro = ref('')
// Filtro por cuenta de apoderado vinculada
const cuenta = ref(route.query.cuenta || null)
const apoderadoId = ref(route.query.apoderado ? Number(route.query.apoderado) : null)
const cuentasApo = ref([])
const cursos = ref([])
const sexos = [{ label: 'Femenino', value: 'F' }, { label: 'Masculino', value: 'M' }, { label: 'Otro / no informa', value: 'X' }]

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/estudiantes',
  () => ({
    run: '', nombres: '', apellido_paterno: '', apellido_materno: '', fecha_nacimiento: '', sexo: null, direccion: '',
    apoderado_nombre: '', apoderado_run: '', apoderado_telefono: '', apoderado_email: '', observaciones: '', curso_id: null,
  }),
  {
    nombre: 'Estudiante',
    params: () => ({
      anio: anio.value,
      curso_id: sinMatricula.value ? undefined : filtroCurso.value || undefined,
      sin_matricula: sinMatricula.value ? 1 : undefined,
      q: filtro.value || undefined,
      cuenta: cuenta.value || undefined,
      apoderado_id: apoderadoId.value || undefined,
    }),
    aForm: e => ({ ...e, fecha_nacimiento: e.fecha_nacimiento || '', matricula: undefined }),
  },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'nombre', label: 'Estudiante', field: 'nombre_completo', align: 'left', sortable: true },
  { name: 'curso', label: 'Curso', field: r => r.matricula?.curso?.nombre || '', align: 'left', sortable: true },
  { name: 'nacimiento', label: 'Nacimiento', field: 'fecha_nacimiento', align: 'left', format: v => (v ? v.split('-').reverse().join('-') : '—') },
  { name: 'apoderado', label: 'Apoderado (ficha)', field: 'apoderado_nombre', align: 'left' },
  { name: 'cuenta', label: 'Cuenta portal familia', field: r => r.cuentas.map(c => c.user.name).join(', '), align: 'left', sortable: true },
]

const activa = e => e.matricula?.estado === 'activa'
const inicialesDe = n => (n || '').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase()

// Usuarios con el rol Apoderado (búsqueda por nombre, correo o RUN)
function buscarApoderados (texto, update) {
  proxy.$axios.get('admin/apoderados/cuentas', { params: { q: texto || undefined } })
    .then(res => update(() => { cuentasApo.value = res.data }))
    .catch(() => update(() => { cuentasApo.value = [] }))
}

function cambiarCuenta (v) {
  if (v === 'sin') apoderadoId.value = null
  cargar()
}

// Clic en una cuenta de la tabla: muestra todos los estudiantes de ese apoderado (otro clic lo quita)
function filtrarPorApoderado (user) {
  if (apoderadoId.value === user.id) {
    apoderadoId.value = null
  } else {
    if (!cuentasApo.value.some(c => c.id === user.id)) cuentasApo.value = [...cuentasApo.value, { ...user, estudiantes_count: '' }]
    apoderadoId.value = user.id
    cuenta.value = null
  }
  cargar()
}

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
  if (apoderadoId.value) buscarApoderados('', fn => fn())
  cargar()
})
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap; align-items: center }
.fichaCard { width: 640px }
.seccion { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #6d8090; border-top: 1px solid #e3eaf1; padding-top: 10px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }

/* Filtro y columna de cuentas de apoderado (color del perfil apoderado) */
.filtroCuenta { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 0 0 12px; padding: 10px 12px; border-radius: 12px; background: #f3fafc; border: 1px solid #d3ecf2 }
.icoCuenta { color: #1685a0; font-size: 15px }
.selApo { min-width: 260px; flex: 0 1 320px }
.apoAvatar { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; background: #e8f8fb; color: #1685a0; font-size: 10px; font-weight: 900; flex: none }
.apoAvatar.mini { width: 24px; height: 24px; font-size: 9px }
.cuentas { display: flex; flex-direction: column; gap: 4px; align-items: flex-start }
.chipCuenta { display: inline-flex; align-items: center; gap: 7px; border: 1px solid #d3ecf2; border-left: 3px solid #1685a0; background: #fff; border-radius: 10px; padding: 3px 9px 3px 4px; cursor: pointer; text-align: left; max-width: 260px }
.chipCuenta:hover, .chipCuenta.activo { background: #e8f8fb }
.chipCuenta .txt { display: flex; flex-direction: column; min-width: 0 }
.chipCuenta b { font-size: 12px; color: #0b2440; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.chipCuenta small { font-size: 10px; color: #6d8090; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
</style>
