<template>
  <div>
    <div class="pageHead">
      <div>
        <h1>{{ soloDocentes ? 'Docentes' : 'Usuarios y permisos' }}</h1>
        <p v-if="soloDocentes">
          Planta docente del establecimiento: usuarios con el permiso <b>Dictar clases</b>.
          Su carga de asignaturas se define en Asignación docente.
        </p>
        <p v-else>
          Cuentas de acceso. Cada usuario recibe los permisos de sus <b>roles</b> más los permisos
          adicionales que se le den directamente; el menú de cada persona muestra solo lo que tiene permitido.
        </p>
      </div>
      <q-btn v-if="$auth.hasPermission(`${modulo}.crear`)" :label="soloDocentes ? '+ Nuevo docente' : '+ Nuevo usuario'" no-caps unelevated class="btnPrimary" @click="nuevo()" />
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>{{ soloDocentes ? 'Planta docente' : 'Usuarios' }} <span class="badge blue q-ml-sm">{{ filas.length }}</span></h3>
        <div class="filtros">
          <q-select
            v-if="!soloDocentes" v-model="filtroRol" :options="roles.map(r => r.name)" clearable
            dense outlined label="Con rol" style="min-width: 170px"
          />
          <q-select
            v-if="!soloDocentes" v-model="filtroPermiso" :options="opcionesFiltro" emit-value map-options clearable
            dense outlined label="Con permiso" style="min-width: 230px"
          />
          <q-input v-model="filtro" dense outlined placeholder="Buscar por nombre, RUN o correo" style="min-width: 260px">
            <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
          </q-input>
        </div>
      </div>

      <q-table
        :rows="filas" :columns="columns" row-key="id" flat :loading="loading" :filter="filtro"
        :pagination="{ rowsPerPage: 20 }" class="adminTable" no-data-label="Sin registros"
      >
        <template #body-cell-name="p">
          <q-td :props="p">
            <div class="who">
              <div class="avatar">{{ initials(p.row.name) }}</div>
              <div>
                <b>{{ p.row.name }}</b>
                <div class="subtle">{{ p.row.email }}</div>
              </div>
            </div>
          </q-td>
        </template>
        <template #body-cell-carga="p">
          <q-td :props="p">
            <span v-if="p.row.asignaciones_count">{{ p.row.asignaciones_count }} asignatura(s)</span>
            <span v-else class="subtle">Sin carga</span>
            <div v-if="p.row.jefaturas_count" class="subtle">Profesor(a) jefe de {{ p.row.jefaturas_count }} curso(s)</div>
          </q-td>
        </template>
        <template #body-cell-roles="p">
          <q-td :props="p">
            <span v-for="rol in p.row.roles" :key="rol" class="badge rol q-mr-xs">{{ rol }}</span>
            <span v-if="!p.row.roles.length" class="subtle">Sin rol</span>
          </q-td>
        </template>
        <template #body-cell-permisos="p">
          <q-td :props="p">
            <PermisosResumen :permisos="p.row.permisos_efectivos" @ver="$auth.hasPermission('usuarios.editar') && abrirPermisos(p.row)" />
          </q-td>
        </template>
        <template #body-cell-estado="p">
          <q-td :props="p">
            <span class="badge" :class="p.row.bloqueado ? 'red' : 'ok'">{{ p.row.bloqueado ? 'Bloqueado' : 'Activo' }}</span>
          </q-td>
        </template>
        <template #body-cell-acciones="p">
          <q-td :props="p" auto-width>
            <MenuAcciones :acciones="[
              { label: 'Editar datos', icon: 'fas fa-pen', permiso: `${modulo}.editar`, click: () => editar(p.row) },
              { label: 'Roles y permisos', icon: 'fas fa-key', permiso: 'usuarios.editar', si: !soloDocentes, click: () => abrirPermisos(p.row) },
              { label: 'Ver carga', icon: 'fas fa-clipboard-user', permiso: 'asignaciones.ver', si: soloDocentes, to: { path: '/admin/asignaciones', query: { docente: p.row.id } } },
              { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: `${modulo}.eliminar`, si: p.row.id !== $auth.user.id, click: () => eliminar(p.row, `¿Eliminar a ${p.row.name}?`) },
            ]" />
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">
            {{ form.id ? 'Editar' : 'Nuevo' }} {{ soloDocentes ? 'docente' : 'usuario' }}
          </q-card-section>
          <q-card-section class="formGrid">
            <q-input v-model="form.name" label="Nombre completo" outlined dense class="full" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="form.run" label="RUN" outlined dense placeholder="12.345.678-5" />
            <q-input v-model="form.telefono" label="Teléfono" outlined dense placeholder="+56 9 1234 5678" />
            <q-input v-model="form.email" label="Correo" type="email" outlined dense class="full" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input
              v-model="form.password" :label="form.id ? 'Nueva clave (dejar vacío para mantener)' : 'Clave'"
              type="password" outlined dense class="full" autocomplete="new-password"
              :rules="[v => (form.id && !v) || (v && v.length >= 6) || 'Mínimo 6 caracteres']" hide-bottom-space
            />

            <q-toggle
              v-model="form.bloqueado" label="Bloquear acceso" color="negative" class="full"
              :disable="form.id === $auth.user.id"
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="permDialog">
      <q-card class="formCard">
        <q-card-section>
          <div class="formTitle">Roles y permisos</div>
          <div class="subtle">{{ permUsuario?.name }} · {{ permUsuario?.email }}</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="permGroup">
            <div class="fieldLabel">Roles</div>
            <q-option-group v-model="rolesSel" type="checkbox" dense :options="roles.map(r => ({ label: r.name, value: r.name }))" />
            <div v-if="!roles.length" class="subtle">No hay roles creados. Créalos en la pestaña Roles.</div>
          </div>
          <q-separator class="q-my-sm" />
          <div class="subtle q-mb-xs">
            Los permisos marcados en gris vienen de los roles elegidos. Marca otros para dárselos solo a esta persona.
          </div>
          <PermisosMatriz v-model="permSel" :bloqueados="porRol" />
          <div v-if="!permisosVista.length" class="subtle q-mt-sm">Sin permisos: al ingresar solo verá Inicio.</div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn no-caps unelevated label="Guardar" class="btnPrimary" :loading="permGuardando" @click="guardarPermisos" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { useRoute } from 'vue-router'
import { useCrud, useCatalogos, mensajeError } from '../composables/useCrud'
import PermisosResumen from './PermisosResumen.vue'
import PermisosMatriz from './PermisosMatriz.vue'
import MenuAcciones from './MenuAcciones.vue'
import { formatoRun } from '../composables/formato'

const props = defineProps({
  // true: sección Docentes (solo usuarios con "Dictar clases"; no se editan permisos)
  soloDocentes: { type: Boolean, default: false },
})

const { proxy } = getCurrentInstance()
const DICTAR_CLASES = 'clases.dictar'
// Permisos CRUD de la sección: "docentes.*" o "usuarios.*"
const modulo = props.soloDocentes ? 'docentes' : 'usuarios'
const catalogos = useCatalogos()
const filtro = ref('')
const route = useRoute()
const filtroPermiso = ref(null)
// Se puede llegar filtrado desde Roles → "Ver usuarios"
const filtroRol = ref(route.query.rol || null)
const roles = ref([])

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'admin/usuarios',
  () => ({ name: '', email: '', run: '', telefono: '', password: '', bloqueado: false, roles: [], permisos: [] }),
  {
    nombre: props.soloDocentes ? 'Docente' : 'Usuario',
    params: () => (props.soloDocentes ? { permiso: DICTAR_CLASES } : {}),
    aForm: u => ({ ...u, run: u.run || '', password: '', roles: [...u.roles], permisos: [...u.permisos] }),
  },
)

const filas = computed(() => rows.value
  .filter(u => !filtroPermiso.value || u.permisos_efectivos.includes(filtroPermiso.value))
  .filter(u => !filtroRol.value || u.roles.includes(filtroRol.value)))

const opcionesFiltro = computed(() => catalogos.value.permisos.flatMap(g => g.permisos.map(p => ({ label: p.label, value: p.name }))))

const columns = computed(() => [
  // field 'email' para que el buscador de la tabla también encuentre por correo
  { name: 'acciones', label: '', field: 'email', align: 'left' },
  { name: 'name', label: 'Nombre', field: 'name', align: 'left', sortable: true },
  { name: 'run', label: 'RUN', field: 'run', align: 'left', format: formatoRun },
  { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left', format: v => v || '—' },
  ...(props.soloDocentes
    ? [{ name: 'carga', label: 'Carga', field: 'asignaciones_count', align: 'left', sortable: true }]
    : [
        { name: 'roles', label: 'Roles', field: r => r.roles.join(' '), align: 'left' },
        { name: 'permisos', label: 'Permisos', field: r => r.permisos_efectivos.length, align: 'left', sortable: true },
      ]),
  { name: 'estado', label: 'Estado', field: 'bloqueado', align: 'center' },
])

// ── Roles y permisos: se ven y editan desde el menú de cada fila ──
const permDialog = ref(false)
const permUsuario = ref(null)
const rolesSel = ref([])
const permSel = ref([]) // solo los directos
const permGuardando = ref(false)

// Permisos que aportan los roles marcados
const porRol = computed(() => [...new Set(
  roles.value.filter(r => rolesSel.value.includes(r.name)).flatMap(r => r.permisos),
)])

// Las casillas muestran directos + heredados; al guardar solo se envían los directos
const permisosVista = computed({
  get: () => [...new Set([...porRol.value, ...permSel.value])],
  set: v => { permSel.value = v.filter(p => !porRol.value.includes(p)) },
})

function abrirPermisos (u) {
  permUsuario.value = u
  rolesSel.value = [...u.roles]
  permSel.value = [...u.permisos]
  permDialog.value = true
}

function guardarPermisos () {
  const u = permUsuario.value
  permGuardando.value = true
  proxy.$axios.put(`admin/usuarios/${u.id}`, {
    name: u.name, email: u.email, run: u.run, telefono: u.telefono, bloqueado: u.bloqueado,
    roles: rolesSel.value,
    permisos: permSel.value.filter(p => !porRol.value.includes(p)),
  })
    .then(() => {
      proxy.$alert.success('Roles y permisos actualizados', u.name)
      permDialog.value = false
      cargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { permGuardando.value = false })
}

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

onMounted(() => {
  cargar()
  if (!props.soloDocentes) {
    proxy.$axios.get('admin/roles').then(res => { roles.value = res.data }).catch(() => {})
  }
})
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap }
.who { display: flex; align-items: center; gap: 10px }
.avatar {
  width: 32px; height: 32px; border-radius: 50%; flex: none;
  display: grid; place-items: center;
  background: #e9eff5; color: #204664; font-weight: 900; font-size: 11px;
}
.badge.rol { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.permGroup { margin-bottom: 8px }
.fieldLabel { font-size: 12px; font-weight: 700; color: #486477; margin-bottom: 4px }
</style>
