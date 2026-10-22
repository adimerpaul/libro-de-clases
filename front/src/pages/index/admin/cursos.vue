<template>
  <div>
    <div class="pageHead">
      <div>
        <h1>Cursos</h1>
        <p>Cursos de cada año escolar con su profesor(a) jefe. Desde aquí se accede a la nómina de estudiantes y a la carga de asignaturas y docentes.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn
          v-if="$auth.hasPermission('cursos.crear')" flat no-caps icon="far fa-copy" :label="`Copiar cursos a ${anio + 1}`"
          :loading="copiando" @click="copiarCursos"
        >
          <q-tooltip>Crea en {{ anio + 1 }} los mismos cursos (nivel y letra) de {{ anio }}, sin profesor jefe ni estudiantes</q-tooltip>
        </q-btn>
        <q-btn v-if="$auth.hasPermission('cursos.crear')" label="+ Nuevo curso" no-caps unelevated class="btnPrimary" @click="nuevo({ anio })" />
      </div>
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Cursos {{ anio }} <span class="badge blue q-ml-sm">{{ rows.length }}</span></h3>
        <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="min-width: 150px" @update:model-value="cargar" />
      </div>

      <q-table
        :rows="rows" :columns="columns" row-key="id" flat :loading="loading"
        :pagination="{ rowsPerPage: 0 }" hide-pagination class="adminTable" no-data-label="Sin cursos para este año"
      >
        <template #body-cell-nombre="p">
          <q-td :props="p"><b>{{ p.row.nombre }}</b></q-td>
        </template>
        <template #body-cell-jefe="p">
          <q-td :props="p">
            <span v-if="p.row.profesor_jefe">{{ p.row.profesor_jefe.name }}</span>
            <span v-else class="badge red">Sin profesor jefe</span>
          </q-td>
        </template>
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Editar', icon: 'fas fa-pen', permiso: 'cursos.editar', click: () => editar(p.row) },
              { label: 'Estudiantes (nómina)', icon: 'fas fa-people-roof', permiso: 'matriculas.ver', to: { path: '/matriculas', query: { curso: p.row.id, anio: p.row.anio } } },
              { label: 'Asignaturas y docentes', icon: 'fas fa-clipboard-user', permiso: 'asignaciones.ver', to: { path: '/admin/asignaciones', query: { curso: p.row.id, anio: p.row.anio } } },
              { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'cursos.eliminar', click: () => eliminar(p.row, `¿Eliminar ${p.row.nombre} ${p.row.anio}? También se quitan sus ${p.row.estudiantes_count} matrículas y ${p.row.asignaciones_count} asignaciones docentes.`) },
            ]" />
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">{{ form.id ? 'Editar curso' : 'Nuevo curso' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-select v-model="form.nivel" :options="catalogos.niveles" label="Nivel" outlined dense :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.letra" label="Letra" outlined dense maxlength="2" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model.number="form.anio" label="Año" type="number" outlined dense :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-select
              v-model="form.profesor_jefe_id" :options="docentes" option-value="id" option-label="name"
              emit-value map-options clearable label="Profesor(a) jefe" outlined dense class="full"
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
import { ref, onMounted, getCurrentInstance } from 'vue'
import { useCrud, useCatalogos, mensajeError } from '../../../composables/useCrud'
import MenuAcciones from '../../../components/MenuAcciones.vue'

definePage({ meta: { title: 'Cursos', permiso: 'cursos.ver' } })

const { proxy } = getCurrentInstance()
const catalogos = useCatalogos()

const actual = new Date().getFullYear()
const anio = ref(actual)
const anios = [actual - 1, actual, actual + 1, actual + 2]
const docentes = ref([])
const copiando = ref(false)

// Prepara el año siguiente con los mismos cursos (sin jefaturas, asignaciones ni estudiantes)
function copiarCursos () {
  const hacia = anio.value + 1
  proxy.$alert.confirm(`¿Crear en ${hacia} los cursos de ${anio.value} que aún no existan? No se copian profesores jefe, asignaturas ni estudiantes.`).onOk(() => {
    copiando.value = true
    proxy.$axios.post('admin/cursos/copiar', { desde_anio: anio.value, hacia_anio: hacia })
      .then(res => {
        proxy.$alert.success(res.data.message)
        anio.value = hacia
        cargar()
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
      .finally(() => { copiando.value = false })
  })
}

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/cursos',
  () => ({ anio: anio.value, nivel: null, letra: 'A', profesor_jefe_id: null }),
  {
    nombre: 'Curso',
    params: () => ({ anio: anio.value }),
    aForm: c => ({ id: c.id, anio: c.anio, nivel: c.nivel, letra: c.letra, profesor_jefe_id: c.profesor_jefe_id }),
  },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'nombre', label: 'Curso', field: 'nombre', align: 'left' },
  { name: 'jefe', label: 'Profesor(a) jefe', field: r => r.profesor_jefe?.name, align: 'left' },
  { name: 'estudiantes', label: 'Estudiantes', field: 'estudiantes_count', align: 'center' },
  { name: 'asignaturas', label: 'Asignaturas', field: 'asignaciones_count', align: 'center' },
]

onMounted(() => {
  cargar()
  proxy.$axios.get('admin/usuarios', { params: { permiso: 'clases.dictar' } })
    .then(res => { docentes.value = res.data.filter(d => !d.bloqueado) })
    .catch(() => {})
})
</script>
