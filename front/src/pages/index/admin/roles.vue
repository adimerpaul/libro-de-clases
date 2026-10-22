<template>
  <div>
    <div class="pageHead">
      <div>
        <h1>Roles</h1>
        <p>
          Un rol agrupa permisos con un nombre (Docente, UTP, Dirección…). Al asignar un rol a un usuario,
          recibe todos sus permisos; si se cambian los permisos del rol, cambian para todos los que lo tienen.
        </p>
      </div>
      <q-btn v-if="$auth.hasPermission('roles.crear')" label="+ Nuevo rol" no-caps unelevated class="btnPrimary" @click="nuevo()" />
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Roles <span class="badge blue q-ml-sm">{{ rows.length }}</span></h3>
      </div>

      <q-table
        :rows="rows" :columns="columns" row-key="id" flat :loading="loading"
        :pagination="{ rowsPerPage: 0 }" hide-pagination class="adminTable" no-data-label="Sin roles"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p" auto-width>
            <MenuAcciones :acciones="[
              { label: 'Editar rol y permisos', icon: 'fas fa-key', permiso: 'roles.editar', click: () => editar(p.row) },
              { label: 'Ver usuarios', icon: 'fas fa-users', permiso: 'usuarios.ver', to: { path: '/admin/usuarios', query: { rol: p.row.name } } },
              { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'roles.eliminar', click: () => eliminar(p.row, `¿Eliminar el rol ${p.row.name}?`) },
            ]" />
          </q-td>
        </template>
        <template #body-cell-name="p">
          <q-td :props="p"><b>{{ p.row.name }}</b></q-td>
        </template>
        <template #body-cell-permisos="p">
          <q-td :props="p"><PermisosResumen :permisos="p.row.permisos" @ver="$auth.hasPermission('roles.editar') && editar(p.row)" /></q-td>
        </template>
        <template #body-cell-usuarios="p">
          <q-td :props="p">
            <span v-if="p.row.users_count">{{ p.row.users_count }} {{ p.row.users_count === 1 ? 'usuario' : 'usuarios' }}</span>
            <span v-else class="subtle">Sin usuarios</span>
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">{{ form.id ? 'Editar rol' : 'Nuevo rol' }}</q-card-section>
          <q-card-section>
            <q-input v-model="form.name" label="Nombre del rol" outlined dense :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <PermisosMatriz v-model="form.permisos" class="q-mt-md" />
            <div v-if="form.id && form.users_count" class="subtle q-mt-sm">
              Los cambios aplican a los {{ form.users_count }} usuario(s) con este rol.
            </div>
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
import { onMounted } from 'vue'
import { useCrud } from '../../../composables/useCrud'
import PermisosResumen from '../../../components/PermisosResumen.vue'
import PermisosMatriz from '../../../components/PermisosMatriz.vue'
import MenuAcciones from '../../../components/MenuAcciones.vue'

definePage({ meta: { title: 'Roles', permiso: 'roles.ver' } })

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/roles',
  () => ({ name: '', permisos: [] }),
  { nombre: 'Rol', aForm: r => ({ ...r, permisos: [...r.permisos] }) },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left' },
  { name: 'name', label: 'Rol', field: 'name', align: 'left', sortable: true },
  { name: 'permisos', label: 'Permisos', field: r => r.permisos.length, align: 'left', sortable: true },
  { name: 'usuarios', label: 'Usuarios', field: 'users_count', align: 'left', sortable: true },
]

onMounted(cargar)
</script>

<style scoped>
.formCard { width: 640px }
</style>
