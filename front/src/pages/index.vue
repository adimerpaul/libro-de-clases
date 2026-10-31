<template>
  <q-layout view="lHh Lpr lFf" class="shell" :class="`role-${perfil.clave}`">
    <q-header class="topbar">
      <q-toolbar class="topbarInner">
        <q-btn flat dense round icon="fas fa-bars" class="lt-md" aria-label="Menú" @click="drawer = !drawer" />
        <div class="crumb">
          <b>{{ titulo }}</b>
          <small>{{ perfil.tag }} · {{ hoy }}</small>
        </div>
        <q-space />
        <span class="sync gt-sm"><i></i> Sincronizado</span>
        <!-- Con más de un rol, el nombre abre el selector de perfil (cambia permisos y menú) -->
        <div v-if="roles.length <= 1" class="user">
          <div class="avatar">{{ initials($auth.user.name) }}</div>
          <div class="utxt gt-xs">
            <b>{{ $auth.user.name }}</b>
            <small>{{ perfil.rol }}</small>
          </div>
        </div>
        <q-btn v-else flat no-caps dense class="user userBtn" :loading="cambiando" aria-label="Cambiar perfil">
          <div class="avatar">{{ initials($auth.user.name) }}</div>
          <div class="utxt gt-xs text-left">
            <b>{{ $auth.user.name }}</b>
            <small>{{ $auth.user.rol_activo || 'Todos mis perfiles' }}</small>
          </div>
          <q-icon name="fas fa-chevron-down" size="10px" class="q-ml-xs flecha" />
          <q-menu anchor="bottom right" self="top right" :offset="[0, 8]" class="menuPerfil">
            <div class="menuHead">
              <b>Cambiar perfil</b>
              <small>El menú y los permisos se ajustan al perfil elegido</small>
            </div>
            <q-list class="q-py-xs">
              <q-item
                v-for="op in opcionesPerfil" :key="op.rol || 'todos'" v-close-popup clickable
                :active="($auth.user.rol_activo || null) === op.rol" active-class="opActiva" @click="cambiarPerfil(op.rol)"
              >
                <q-item-section avatar>
                  <div class="opIcono" :class="`role-${op.clave}`"><q-icon :name="op.icono" size="13px" /></div>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ op.label }}</q-item-label>
                  <q-item-label caption>{{ op.tag }}</q-item-label>
                </q-item-section>
                <q-item-section v-if="($auth.user.rol_activo || null) === op.rol" side>
                  <q-icon name="fas fa-check" size="12px" color="primary" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
        <q-btn label="Salir" no-caps unelevated class="btnSalir" :loading="saliendo" @click="logout" />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="drawer" show-if-above :width="258" class="sidebar">
      <div class="sideBrand">
        <div class="brandMark"><q-icon name="fas fa-book-open" size="18px" /></div>
        <div class="brandText">
          <b>Ecosistema 4.2</b>
          <small>{{ perfil.rol }}</small>
        </div>
      </div>

      <div v-for="grupo in menu" :key="grupo.title" class="navGroup">
        <div v-if="grupo.title !== 'Trabajo'" class="navLabel">{{ grupo.title }}</div>
        <router-link
          v-for="item in grupo.links" :key="item.key" :to="item.link"
          class="navItem" :class="{ pendiente: item.pendiente }"
          :active-class="item.link === '/' ? '' : 'active'" exact-active-class="active"
        >
          <q-icon :name="item.icon" class="navIcon" />
          <span class="navText">{{ item.title }}</span>
          <span v-if="item.pendiente" class="navTag">Pendiente</span>
        </router-link>
      </div>

      <div class="sideFoot">
        Ecosistema 4.2 · Libro de Clases 3.0<br>
        Los módulos marcados como pendientes aún están en construcción.<br>v{{ $version }}
      </div>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue'
import { useRoute } from 'vue-router'
import { MODULOS, menuVisible, perfilDe, perfilDeRol } from '../composables/menu'
import { mensajeError } from '../composables/useCrud'

const { proxy } = getCurrentInstance()
const route = useRoute()

const drawer = ref(false)
const saliendo = ref(false)

// Menú lateral: se define en src/composables/menu.js (ui3/app.js ROUTES)
const menu = computed(() => menuVisible(proxy.$auth))
const perfil = computed(() => perfilDe(proxy.$auth.user))

// Las páginas de módulos pendientes no tienen definePage propio: el título sale del menú
const titulo = computed(() => route.meta.title || MODULOS[route.params.modulo]?.title || 'Ecosistema 4.2')

const fecha = new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
const hoy = fecha.charAt(0).toUpperCase() + fecha.slice(1)

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

// ── Cambio de perfil (rol activo de la sesión) ──
const ICONOS = {
  docente: 'fas fa-chalkboard-user', utp: 'fas fa-chart-line', diferencial: 'fas fa-puzzle-piece', inspectoria: 'fas fa-shield-halved',
  convivencia: 'fas fa-handshake', sostenedor: 'fas fa-building-columns', apoderado: 'fas fa-house-chimney-user',
}
const roles = computed(() => proxy.$auth.user.roles || [])
const opcionesPerfil = computed(() => [
  ...roles.value.map(rol => {
    const p = perfilDeRol(rol)
    return { rol, label: rol, clave: p.clave, tag: p.tag, icono: ICONOS[p.clave] || 'fas fa-user' }
  }),
  { rol: null, label: 'Todos mis perfiles', clave: 'docente', tag: 'Permisos de todos tus roles juntos', icono: 'fas fa-layer-group' },
])
const cambiando = ref(false)

function cambiarPerfil (rol) {
  if ((proxy.$auth.user.rol_activo || null) === rol) return
  cambiando.value = true
  proxy.$axios.put('perfil', { rol })
    .then(res => {
      proxy.$auth.setSesion(res.data)
      proxy.$alert.success(`Perfil: ${rol || 'todos mis perfiles'}`)
      // Si la página actual ya no está permitida con el nuevo perfil, vuelve al inicio
      const permiso = route.meta.permiso
      if (permiso && !proxy.$auth.hasPermission(permiso)) proxy.$router.push('/')
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cambiando.value = false })
}

function logout () {
  saliendo.value = true
  proxy.$axios.post('logout')
    .catch(() => {})
    .finally(() => {
      saliendo.value = false
      proxy.$auth.logout()
      proxy.$router.push('/login')
    })
}
</script>

<style scoped>
.topbar { background: #fff; color: #15324a; border-bottom: 1px solid #d9e3ec }
.topbarInner { min-height: 70px; padding: 0 24px; gap: 8px }
.crumb b { display: block; font-size: 15px }
.crumb small { color: #6d8090 }
.sync {
  display: flex; align-items: center; gap: 7px;
  font-size: 11px; color: #486477;
  background: #eef7f2; border: 1px solid #cfe6da; padding: 7px 9px; border-radius: 999px;
}
.sync i { width: 8px; height: 8px; border-radius: 50%; background: #22a06b }
.user { display: flex; align-items: center; gap: 8px; padding-left: 8px }
.avatar {
  width: 35px; height: 35px; border-radius: 50%;
  display: grid; place-items: center;
  background: var(--roleSoft); color: var(--role); font-weight: 900; font-size: 12px;
}
.utxt b { display: block; font-size: 11px }
.utxt small { display: block; color: #6d8090; font-size: 11px }
.userBtn { border-radius: 12px; padding: 4px 10px 4px 4px }
.userBtn:hover { background: var(--roleSoft) }
.flecha { color: #6d8090 }
.menuHead { padding: 12px 14px 8px; border-bottom: 1px solid #eef3f7; max-width: 280px }
.menuHead b { display: block; font-size: 13px; color: #0b2440 }
.menuHead small { color: #6d8090; font-size: 11px }
.opIcono { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--roleSoft); color: var(--role) }
.opActiva { background: #f2f7fc; color: #0b2440; font-weight: 700 }
.btnSalir { background: #10263b; color: #fff; border-radius: 11px; font-weight: 800; font-size: 12px }

/* QDrawer pone la clase en .q-drawer__content, que no lleva el data-v del scope */
:deep(.sidebar) { background: linear-gradient(180deg, #0a223d, #0c3154); padding: 16px 13px; color: #d7e5ef }
.sideBrand { display: flex; align-items: center; gap: 10px; padding: 7px 7px 18px; border-bottom: 1px solid #ffffff12 }
.brandMark {
  width: 42px; height: 42px; border-radius: 12px; flex: none;
  background: linear-gradient(145deg, #2c81c5, #0f4f85);
  display: grid; place-items: center; color: #fff;
  box-shadow: 0 14px 34px #0004;
}
.brandText b { color: #fff; font-size: 14px }
.brandText small { display: block; color: #9db5c8; font-size: 10px }
.navGroup { margin-top: 4px }
.navLabel { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; color: #7995aa; padding: 8px 10px 4px }
.navItem {
  display: flex; align-items: center; gap: 10px;
  color: #cbd9e4; padding: 9px 10px; border-radius: 10px; margin: 2px 0;
  font-size: 13px; line-height: 1.25; text-decoration: none;
}
.navItem:hover { background: #ffffff0e }
.navItem.active { background: #ffffff14; color: #fff; box-shadow: inset 3px 0 0 #ff4e5c }
.navItem.pendiente { color: #91a9bc }
.navIcon { width: 25px; text-align: center; font-size: 15px }
.navText { flex: 1 }
.navTag {
  font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em;
  padding: 3px 6px; border-radius: 999px; background: #ffffff10; border: 1px solid #ffffff1c; color: #f3c27a;
}
.sideFoot { border-top: 1px solid #ffffff12; margin-top: 18px; padding: 14px 8px; font-size: 10px; color: #8fa8bb; line-height: 1.5 }

@media (max-width: 720px) {
  .topbarInner { padding: 0 12px }
}
</style>
