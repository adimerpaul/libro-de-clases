<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm"
         style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver usuarios</div>
    </div>

    <template v-else>
      <div class="row items-end q-col-gutter-sm q-mb-md">
        <div class="col">
          <div class="text-h5 text-weight-bold">Usuarios</div>
          <div class="text-body2 text-grey-6">Cuentas con acceso al sistema</div>
        </div>
        <div class="col-auto row items-center q-gutter-sm">
          <q-input v-model="filter" placeholder="Buscar…" dense outlined rounded
                   clearable bg-color="white" style="width:200px">
            <template v-slot:prepend><q-icon name="search" /></template>
          </q-input>
          <q-btn outline rounded no-caps color="grey-7" icon="refresh" @click="usersGet">
            <q-tooltip>Actualizar</q-tooltip>
          </q-btn>
          <q-btn v-if="canCrear" rounded unelevated no-caps color="primary"
                 label="Nuevo usuario" icon="add" @click="userNew" />
        </div>
      </div>

      <q-table
        :rows="users"
        :columns="columns"
        row-key="id"
        dense
        flat
        bordered
        class="rounded-borders"
        table-header-class="bg-grey-1 text-grey-7 text-uppercase"
        :rows-per-page-options="[0]"
        :filter="filter"
        :loading="loading"
      >

        <template v-slot:body-cell-avatar="props">
          <q-td :props="props" auto-width>
            <q-avatar
              size="34px"
              :style="canEditar ? 'cursor:pointer;border:2px solid #e2e8f0' : 'border:2px solid #e2e8f0'"
              @click="canEditar && openDialog(props.row)"
            >
              <img :src="imgUrl(props.row.avatar || 'default.png')"
                   style="object-fit:cover;width:100%;height:100%"
                   @error="$event.target.src = imgUrl('default.png')" />
            </q-avatar>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn-dropdown
              v-if="canEditar || canEliminar || canPermisos"
              label="Opciones" no-caps size="10px" dense rounded unelevated color="primary"
            >
              <q-list>
                <q-item v-if="canEditar" clickable v-close-popup @click="openDialog(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canPermisos" clickable v-close-popup @click="openPermisos(props.row)">
                  <q-item-section avatar><q-icon name="admin_panel_settings" color="primary" /></q-item-section>
                  <q-item-section>
                    <q-item-label>Permisos</q-item-label>
                    <q-item-label caption>Administrar los permisos del usuario</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="canEditar" clickable v-close-popup @click="userResetPassword(props.row)">
                  <q-item-section avatar><q-icon name="lock_reset" /></q-item-section>
                  <q-item-section><q-item-label>Restablecer contraseña</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEditar" clickable v-close-popup @click="userToggleBloqueo(props.row)">
                  <q-item-section avatar>
                    <q-icon :name="props.row.bloqueado ? 'lock_open' : 'block'"
                            :color="props.row.bloqueado ? 'positive' : 'warning'" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ props.row.bloqueado ? 'Desbloquear usuario' : 'Bloquear usuario' }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="canEliminar" clickable v-close-popup @click="userDelete(props.row.id)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>

        <template v-slot:body-cell-estado="props">
          <q-td :props="props">
            <q-badge v-if="props.row.bloqueado" rounded color="red-1" text-color="negative" class="text-weight-bold">
              <q-icon name="block" size="12px" class="q-mr-xs" />Bloqueado
            </q-badge>
            <q-badge v-else rounded color="green-1" text-color="positive" class="text-weight-bold">Activo</q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-perms="props">
          <q-td :props="props">
            <div :style="canPermisos ? 'cursor:pointer' : ''"
                 @click="canPermisos && openPermisos(props.row)">
              <q-badge v-if="(props.row.permissions||[]).length" rounded color="teal-1" text-color="primary" class="text-weight-bold">
                {{ (props.row.permissions||[]).length }} permisos
              </q-badge>
              <q-badge v-else rounded color="grey-3" text-color="grey-7">Sin permisos</q-badge>
              <q-tooltip v-if="canPermisos">Administrar permisos</q-tooltip>
            </div>
          </q-td>
        </template>
      </q-table>

      <!-- ── Dialog único: datos + avatar + permisos ── -->
      <q-dialog v-model="dialog" persistent :maximized="$q.screen.lt.sm">
        <q-card class="user-card">
          <q-card-section class="row items-center q-pb-sm bg-primary text-white">
            <q-icon name="person" size="20px" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-bold">{{ actionUser }} usuario</span>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="dialog = false" />
          </q-card-section>

          <q-card-section class="user-card__body">
            <q-form ref="formRef" @submit.prevent="saveUser">
              <div class="row q-col-gutter-md items-start q-mb-sm">
                <div class="col-auto">
                  <div class="avatar-upload" @click="$refs.fileInput.click()">
                    <q-avatar size="80px" style="border:2px solid #e2e8f0">
                      <img :src="avatarPreview || (user.avatar ? imgUrl(user.avatar) : imgUrl('default.png'))"
                           style="object-fit:cover;width:100%;height:100%"
                           @error="$event.target.src = imgUrl('default.png')" />
                    </q-avatar>
                    <div class="avatar-upload__overlay">
                      <q-icon name="photo_camera" size="22px" color="white" />
                    </div>
                  </div>
                  <input ref="fileInput" type="file" accept="image/*" style="display:none"
                         @change="onAvatarChange" />
                  <div class="text-caption text-grey-6 text-center q-mt-xs" style="width:80px">Foto</div>
                </div>

                <div class="col">
                  <div class="row q-col-gutter-sm">
                    <div class="col-12">
                      <q-input v-model="user.name" label="Nombre completo" dense outlined
                               :rules="[v => !!v || 'Requerido']" />
                    </div>
                    <div class="col-12">
                      <q-input v-model="user.username" label="Nombre de usuario" dense outlined
                               :rules="[v => !!v || 'Requerido']" :disable="!!user.id" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-12 col-sm-6">
                  <q-input v-model="user.email" label="Email" dense outlined />
                </div>
                <div class="col-12 col-sm-6">
                  <q-input v-model="user.celular" label="Celular" dense outlined />
                </div>
                <div class="col-12 col-sm-6">
                  <q-input v-model="user.ci" label="Cédula de identidad" dense outlined />
                </div>
                <div v-if="!user.id" class="col-12 col-sm-6">
                  <q-input v-model="user.password" label="Contraseña" dense outlined
                           :rules="[v => !!v || 'Requerido']" />
                </div>
              </div>

              <!-- Permisos — solo con Gestionar Permisos -->
              <template v-if="canPermisos">
                <q-separator class="q-mb-sm" />
                <PermisosSelector :permissions="permissions" :loading="loadingPerms" />
              </template>

              <div class="row justify-end q-gutter-sm q-mt-md">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialog = false" />
                <q-btn color="primary" :label="user.id ? 'Guardar cambios' : 'Crear usuario'"
                       type="submit" no-caps :loading="saving" icon-right="save" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <!-- ── Dialog dedicado: solo permisos del usuario ── -->
      <q-dialog v-model="permDialog" persistent :maximized="$q.screen.lt.sm">
        <q-card class="user-card">
          <q-card-section class="row items-center q-pb-sm bg-primary text-white">
            <q-icon name="admin_panel_settings" size="20px" class="q-mr-sm" />
            <div class="column">
              <span class="text-subtitle1 text-weight-bold">Permisos</span>
              <span class="text-caption">{{ permUser.name }} ({{ permUser.username }})</span>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="permDialog = false" />
          </q-card-section>

          <q-card-section class="user-card__body">
            <div class="text-caption text-grey-7 q-mb-sm">
              Pase el cursor sobre un permiso para ver qué habilita.
            </div>

            <PermisosSelector :permissions="permissions" :loading="loadingPerms" max-height="52vh" />

            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="permDialog = false" />
              <q-btn color="primary" label="Guardar permisos" no-caps :loading="savingPerms"
                     icon-right="save" @click="savePermisos" />
            </div>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import PermisosSelector from '../../../components/PermisosSelector.vue'

const { proxy } = getCurrentInstance()

// ── Permisos reactivos ───────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Usuarios'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Usuarios'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Usuarios'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Usuarios'))
const canPermisos = computed(() => proxy.$store.hasPermission('Gestionar Permisos'))

// ── Estado ───────────────────────────────────────────────────────
const users       = ref([])
const user        = ref({})
const dialog      = ref(false)
const loading     = ref(false)
const saving      = ref(false)
const actionUser  = ref('')
const filter      = ref('')

const permissions   = ref([])
const loadingPerms  = ref(false)
const avatarFile    = ref(null)
const avatarPreview = ref(null)

// Dialog dedicado de permisos (opción "Permisos" del menú de cada usuario)
const permDialog  = ref(false)
const permUser    = ref({})
const savingPerms = ref(false)

const IMG_BASE = (import.meta.env.VITE_API_BACK || '').replace(/\/api\/?$/, '')

function imgUrl (filename) {
  return IMG_BASE + '/images/' + filename
}

const columns = [
  { name: 'avatar',   label: '',         align: 'center' },
  { name: 'actions',  label: 'Acciones', align: 'center' },
  { name: 'name',     label: 'Nombre',   align: 'left', field: 'name',     sortable: true },
  { name: 'username', label: 'Usuario',  align: 'left', field: 'username', sortable: true },
  { name: 'email',    label: 'Email',    align: 'left', field: 'email' },
  { name: 'celular',  label: 'Celular',  align: 'left', field: 'celular' },
  { name: 'ci',       label: 'CI',       align: 'left', field: 'ci' },
  { name: 'estado',   label: 'Estado',   align: 'left', field: row => (row.bloqueado ? 'Bloqueado' : 'Activo'), sortable: true },
  { name: 'perms',    label: 'Permisos', align: 'left' },
]

// ── Carga inicial — espera a que se resuelvan los permisos ───────
let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; usersGet() }
}, { immediate: true })

// ── CRUD ─────────────────────────────────────────────────────────
function usersGet () {
  loading.value = true
  proxy.$axios.get('users').then(res => {
    users.value = res.data
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

async function openDialog (row = null) {
  avatarFile.value    = null
  avatarPreview.value = null
  permissions.value   = []

  if (row) {
    user.value    = { ...row }
    actionUser.value = 'Editar'
  } else {
    user.value    = { name: '', username: '', email: '', celular: '', ci: '', password: '' }
    actionUser.value = 'Nuevo'
  }

  dialog.value = true

  if (canPermisos.value) await permissionsGet(row)
}

function userNew () { openDialog(null) }

// ── Permisos ─────────────────────────────────────────────────────

// Trae el catálogo de permisos (con su módulo y descripción) y marca
// los que ya tiene el usuario. Sin `row` (usuario nuevo) van todos vacíos.
async function permissionsGet (row = null) {
  loadingPerms.value = true
  try {
    const [all, userPermIds] = await Promise.all([
      proxy.$axios.get('permissions').then(r => r.data),
      row ? proxy.$axios.get('users/' + row.id + '/permissions').then(r => r.data) : Promise.resolve([]),
    ])
    permissions.value = all.map(p => ({ ...p, checked: userPermIds.includes(p.id) }))
  } catch {
    proxy.$alert.error('Error cargando permisos')
  } finally {
    loadingPerms.value = false
  }
}

async function openPermisos (row) {
  permUser.value    = { ...row }
  permissions.value = []
  permDialog.value  = true
  await permissionsGet(row)
}

// Guarda los permisos marcados y devuelve los nombres vigentes.
async function permissionsPut (userId) {
  const ids = permissions.value.filter(p => p.checked).map(p => p.id)
  const res = await proxy.$axios.put('users/' + userId + '/permissions', { permissions: ids })

  // Si el usuario editado es el que está logueado, refresca sus permisos
  // en el store para que el menú y los botones reaccionen al instante.
  if (Number(userId) === Number(proxy.$store.user?.id)) {
    const currentPermissions = res.data || []
    proxy.$store.permissions = currentPermissions
    localStorage.setItem('permissionsUrme', JSON.stringify(currentPermissions))
  }
}

async function savePermisos () {
  savingPerms.value = true
  try {
    await permissionsPut(permUser.value.id)
    permDialog.value = false
    proxy.$alert.success('Permisos actualizados')
    usersGet()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar los permisos')
  } finally {
    savingPerms.value = false
  }
}

function onAvatarChange (e) {
  const file = e.target.files[0]
  if (!file) return
  avatarFile.value    = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function saveUser () {
  saving.value = true
  try {
    let savedId = user.value.id

    if (savedId) {
      await proxy.$axios.put('users/' + savedId, user.value)
    } else {
      const res = await proxy.$axios.post('users', user.value)
      savedId = res.data.id
    }

    if (avatarFile.value) {
      const form = new FormData()
      form.append('avatar', avatarFile.value)
      const res = await proxy.$axios.post('users/' + savedId + '/avatar', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      user.value.avatar = res.data.avatar
    }

    if (canPermisos.value) await permissionsPut(savedId)

    dialog.value = false
    proxy.$alert.success(user.value.id ? 'Usuario actualizado' : 'Usuario creado')
    usersGet()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    saving.value = false
  }
}

function userDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el usuario?').onOk(() => {
    loading.value = true
    proxy.$axios.delete('users/' + id).then(() => {
      proxy.$alert.success('Usuario eliminado')
      usersGet()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    }).finally(() => { loading.value = false })
  })
}

function userResetPassword (row) {
  proxy.$alert.dialog(`¿Restablecer contraseña de "${row.username}" a 123456?`).onOk(() => {
    proxy.$axios.put('users/' + row.id + '/reset-password').then(() => {
      proxy.$alert.success(`Contraseña de ${row.username} restablecida a 123456`)
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al restablecer')
    })
  })
}

function userToggleBloqueo (row) {
  const bloquear = !row.bloqueado
  const texto = bloquear
    ? `¿Bloquear a "${row.username}"? Se cerrarán todas sus sesiones activas.`
    : `¿Desbloquear a "${row.username}"?`

  proxy.$alert.dialog(texto).onOk(() => {
    loading.value = true
    proxy.$axios.put('users/' + row.id + '/bloqueo').then(res => {
      proxy.$alert.success(res.data.message)
      usersGet()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al cambiar el estado')
    }).finally(() => { loading.value = false })
  })
}
</script>

<style scoped>
.user-card {
  width: min(96vw, 720px);
  max-width: 720px;
  display: flex;
  flex-direction: column;
}

.user-card__body {
  overflow-y: auto;
  max-height: calc(90vh - 56px);
  padding: 14px 16px;
}

.avatar-upload {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  cursor: pointer;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.18);
}

.avatar-upload__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.38);
  opacity: 0;
  transition: opacity 0.18s;
  border-radius: 50%;
}

.avatar-upload:hover .avatar-upload__overlay {
  opacity: 1;
}

@media (max-width: 599px) {
  .user-card {
    width: 100%;
    max-width: none;
    height: 100%;
    border-radius: 0;
  }

  .user-card__body { max-height: calc(100vh - 56px); }
}
</style>
