<template>
  <div>
    <div class="pageHead">
      <div>
        <h1>Asignación docente</h1>
        <p>Qué docente dicta cada asignatura en cada curso. Cada asignatura tiene un solo docente responsable por curso; esta carga alimenta el Libro de Clases.</p>
      </div>
      <q-btn v-if="$auth.hasPermission('asignaciones.crear')" label="+ Asignar asignatura" no-caps unelevated class="btnPrimary" @click="nuevo({ curso_id: cursoId, docente_id: docenteId })" />
    </div>

    <div class="card">
      <div class="cardHead">
        <div class="filtros">
          <q-select v-model="anio" :options="anios" dense outlined label="Año" style="width: 110px" @update:model-value="cambiarAnio" />
          <q-select
            v-model="cursoId" :options="cursos" option-value="id" option-label="nombre" emit-value map-options
            clearable dense outlined label="Curso" style="min-width: 180px" @update:model-value="filtrar"
          />
          <q-select
            v-model="docenteId" :options="docentes" option-value="id" option-label="name" emit-value map-options
            clearable dense outlined label="Docente" style="min-width: 240px" @update:model-value="filtrar"
          />
        </div>
        <div class="resumen">
          <span class="badge blue">{{ rows.length }} asignaciones</span>
          <span class="badge">{{ totalHoras }} h semanales</span>
          <span v-if="cursoSel" class="badge" :class="cursoSel.profesor_jefe ? 'ok' : 'red'">
            Jefatura: {{ cursoSel.profesor_jefe?.name || 'sin asignar' }}
          </span>
        </div>
      </div>

      <q-table
        :rows="rows" :columns="columns" row-key="id" flat :loading="loading"
        :pagination="{ rowsPerPage: 25 }" class="adminTable" no-data-label="Sin asignaciones con estos filtros"
      >
        <template #body-cell-asignatura="p">
          <q-td :props="p">
            <AsignaturaIcono :nombre="p.row.asignatura.nombre" :color="p.row.asignatura.color" />
            <b>{{ p.row.asignatura.nombre }}</b>
            <span class="subtle q-ml-xs">{{ p.row.asignatura.codigo }}</span>
          </q-td>
        </template>
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Cambiar docente u horas', icon: 'fas fa-pen', permiso: 'asignaciones.editar', click: () => editar(p.row) },
              { label: 'Quitar asignación', icon: 'far fa-trash-can', peligro: true, permiso: 'asignaciones.eliminar', click: () => eliminar(p.row, `¿Quitar ${p.row.asignatura.nombre} de ${p.row.curso.nombre} a ${p.row.docente.name}?`) },
            ]" />
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">{{ form.id ? 'Editar asignación' : 'Asignar asignatura' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-select
              v-model="form.curso_id" :options="cursos" option-value="id" option-label="nombre" emit-value map-options
              label="Curso" outlined dense :disable="!!form.id" :rules="[v => !!v || 'Requerido']" hide-bottom-space
            />
            <q-input v-model.number="form.horas_semanales" label="Horas semanales" type="number" min="1" max="40" outlined dense />
            <q-select
              v-model="form.asignatura_id" :options="asignaturasDisponibles" option-value="id" option-label="nombre"
              emit-value map-options label="Asignatura" outlined dense class="full" :disable="!!form.id"
              :rules="[v => !!v || 'Requerido']" hide-bottom-space
              :hint="form.curso_id && !form.id ? 'Solo asignaturas activas que aún no tienen docente en el curso' : ''"
            />
            <q-select
              v-model="form.docente_id" :options="docentes" option-value="id" option-label="name"
              emit-value map-options label="Docente" outlined dense class="full"
              :rules="[v => !!v || 'Requerido']" hide-bottom-space
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCrud } from '../../../composables/useCrud'
import AsignaturaIcono from '../../../components/AsignaturaIcono.vue'
import MenuAcciones from '../../../components/MenuAcciones.vue'

definePage({ meta: { title: 'Asignación docente', permiso: 'asignaciones.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()

const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(route.query.anio ? Number(route.query.anio) : actual)
const cursoId = ref(route.query.curso ? Number(route.query.curso) : null)
const docenteId = ref(route.query.docente ? Number(route.query.docente) : null)

const cursos = ref([])
const asignaturas = ref([])
const docentes = ref([])

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/asignaciones',
  () => ({ curso_id: null, asignatura_id: null, docente_id: null, horas_semanales: null }),
  {
    nombre: 'Asignación',
    params: () => ({ anio: anio.value, curso_id: cursoId.value || undefined, docente_id: docenteId.value || undefined }),
    aForm: a => ({ id: a.id, curso_id: a.curso_id, asignatura_id: a.asignatura_id, docente_id: a.docente_id, horas_semanales: a.horas_semanales }),
  },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'curso', label: 'Curso', field: r => r.curso.nombre, align: 'left', sortable: true },
  { name: 'asignatura', label: 'Asignatura', field: r => r.asignatura.nombre, align: 'left', sortable: true },
  { name: 'docente', label: 'Docente', field: r => r.docente.name, align: 'left', sortable: true },
  { name: 'horas', label: 'Horas', field: 'horas_semanales', align: 'center', format: v => v ?? '—' },
]

const cursoSel = computed(() => cursos.value.find(c => c.id === cursoId.value))
const totalHoras = computed(() => rows.value.reduce((s, r) => s + (r.horas_semanales || 0), 0))

// Asignaturas que todavía no tienen docente en el curso elegido en el formulario
const ocupadas = ref([])
const asignaturasDisponibles = computed(() => {
  if (form.value.id) return asignaturas.value
  return asignaturas.value.filter(a => a.activo && !ocupadas.value.includes(a.id))
})

function filtrar () {
  router.replace({ query: { anio: anio.value, curso: cursoId.value || undefined, docente: docenteId.value || undefined } })
  cargar()
}

function cambiarAnio () {
  cursoId.value = null
  cargarCursos()
  filtrar()
}

function cargarCursos () {
  return proxy.$axios.get('admin/cursos', { params: { anio: anio.value } })
    .then(res => { cursos.value = res.data })
}

// Al elegir curso en el formulario se consultan sus asignaturas ya cubiertas
watch(() => [dialog.value, form.value.curso_id], ([abierto, curso]) => {
  ocupadas.value = []
  if (!abierto || !curso || form.value.id) return
  proxy.$axios.get('admin/asignaciones', { params: { curso_id: curso } })
    .then(res => { ocupadas.value = res.data.map(a => a.asignatura_id) })
})

onMounted(() => {
  cargar()
  cargarCursos().catch(() => {})
  proxy.$axios.get('admin/asignaturas').then(res => { asignaturas.value = res.data }).catch(() => {})
  proxy.$axios.get('admin/usuarios', { params: { permiso: 'clases.dictar' } })
    .then(res => { docentes.value = res.data.map(d => ({ ...d, disable: d.bloqueado })) })
    .catch(() => {})
})
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap }
.resumen { display: flex; gap: 6px; flex-wrap: wrap }
</style>
