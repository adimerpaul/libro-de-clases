<template>
  <div>
    <div class="pageHead">
      <div>
        <h1>Asignaturas</h1>
        <p>Catálogo de asignaturas del establecimiento. Una asignatura desactivada ya no se ofrece al asignar docentes, pero conserva su historial.</p>
      </div>
      <q-btn v-if="$auth.hasPermission('asignaturas.crear')" label="+ Nueva asignatura" no-caps unelevated class="btnPrimary" @click="nuevo()" />
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Catálogo</h3>
        <q-input v-model="filtro" dense outlined placeholder="Buscar asignatura o código" style="min-width: 260px">
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="18px" /></template>
        </q-input>
      </div>

      <q-table
        :rows="rows" :columns="columns" row-key="id" flat :loading="loading" :filter="filtro"
        :pagination="{ rowsPerPage: 20 }" class="adminTable" no-data-label="Sin asignaturas registradas"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Editar', icon: 'fas fa-pen', permiso: 'asignaturas.editar', click: () => editar(p.row) },
              { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'asignaturas.eliminar', click: () => eliminar(p.row, `¿Eliminar la asignatura ${p.row.nombre}?`) },
            ]" />
          </q-td>
        </template>
        <template #body-cell-nombre="p">
          <q-td :props="p">
            <AsignaturaIcono :nombre="p.row.nombre" :color="p.row.color" />
            <b>{{ p.row.nombre }}</b>
          </q-td>
        </template>
        <template #body-cell-activo="p">
          <q-td :props="p">
            <span class="badge" :class="p.row.activo ? 'ok' : 'red'">{{ p.row.activo ? 'Activa' : 'Inactiva' }}</span>
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">{{ form.id ? 'Editar asignatura' : 'Nueva asignatura' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-input v-model="form.nombre" label="Nombre" outlined dense class="full" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.codigo" label="Código" outlined dense maxlength="10" hint="Ej. MAT, LEN" :rules="[v => !!v || 'Requerido']" />
            <q-input v-model="form.color" label="Color" outlined dense type="color" />
            <q-toggle v-model="form.activo" label="Asignatura activa" class="full" />
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
import { ref, onMounted } from 'vue'
import { useCrud } from '../../../composables/useCrud'
import MenuAcciones from '../../../components/MenuAcciones.vue'
import AsignaturaIcono from '../../../components/AsignaturaIcono.vue'

definePage({ meta: { title: 'Asignaturas', permiso: 'asignaturas.ver' } })

const filtro = ref('')

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/asignaturas',
  () => ({ nombre: '', codigo: '', color: '#0f4f85', activo: true }),
  { nombre: 'Asignatura' },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'nombre', label: 'Asignatura', field: 'nombre', align: 'left', sortable: true },
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left', sortable: true },
  { name: 'cursos', label: 'Cursos', field: 'asignaciones_count', align: 'center', sortable: true },
  { name: 'activo', label: 'Estado', field: 'activo', align: 'center' },
]

onMounted(cargar)
</script>
