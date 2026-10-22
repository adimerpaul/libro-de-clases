<template>
  <q-layout view="lHh Lpr lFf" class="shell">
    <q-header class="topbar">
      <q-toolbar class="topbarInner">
        <q-btn flat dense round icon="fas fa-bars" class="lt-md" aria-label="Menú" @click="drawer = !drawer" />
        <div class="crumb">
          <b>{{ $route.meta.title || 'Libro de Clases 3.0' }}</b>
          <small>{{ hoy }}</small>
        </div>
        <q-space />
        <div class="user">
          <div class="avatar">{{ initials($auth.user.name) }}</div>
          <div class="utxt gt-xs">
            <b>{{ $auth.user.name }}</b>
            <small>{{ $auth.user.email }}</small>
          </div>
        </div>
        <q-btn label="Salir" no-caps unelevated class="btnSalir" :loading="saliendo" @click="logout" />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="drawer" show-if-above :width="224" class="sidebar">
      <div class="sideBrand">
        <div class="brandMark"><q-icon name="fas fa-book-open" size="16px" /></div>
        <div class="brandText">
          <b>LIBRO 3.0</b>
          <small>Ecosistema educativo</small>
        </div>
      </div>

      <div v-for="grupo in menuVisible" :key="grupo.title" class="navGroup">
        <div class="navLabel">{{ grupo.title }}</div>
        <router-link
          v-for="item in grupo.links" :key="item.link" :to="item.link"
          class="navItem" :active-class="item.link === '/' ? '' : 'active'" exact-active-class="active"
        >
          <q-icon :name="item.icon" class="navIcon" />
          <span>{{ item.title }}</span>
        </router-link>
      </div>

      <div class="sideFoot">
        Libro 3.0 · Ecosistema de apoyo a la comunidad educativa.<br>v{{ $version }}
      </div>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue'
import { PERMISOS_ADMIN } from '../composables/adminModulos'

const { proxy } = getCurrentInstance()

const drawer = ref(false)
const saliendo = ref(false)

// Menú lateral (ver ui2/app.js NAV): agregar cada módulo aquí cuando exista su página
const menu = [
  {
    title: 'Trabajo diario',
    links: [
      { title: 'Inicio', icon: 'fas fa-house', link: '/' },
      { title: 'Libro de clases', icon: 'fas fa-book', link: '/libros', permiso: ['libros.ver', 'clases.dictar'] },
      { title: 'Planificador', icon: 'far fa-calendar-days', link: '/planificador', permiso: 'clases.dictar' },
      { title: 'Evaluaciones', icon: 'fas fa-file-pen', link: '/evaluaciones', permiso: 'clases.dictar' },
    ],
  },
  {
    title: 'Administración',
    links: [
      // Cursos, asignaturas, docentes, asignación docente y usuarios van como pestañas (pages/index/admin.vue)
      { title: 'Administrador', icon: 'fas fa-gears', link: '/admin', permiso: PERMISOS_ADMIN },
      { title: 'Estudiantes', icon: 'fas fa-user-graduate', link: '/estudiantes', permiso: 'estudiantes.ver' },
      { title: 'Matrículas', icon: 'fas fa-people-roof', link: '/matriculas', permiso: 'matriculas.ver' },
    ],
  },
]

// Cada enlace con `permiso` solo se muestra a quien lo tiene; los grupos vacíos se ocultan
const menuVisible = computed(() => menu
  .map(g => ({ ...g, links: g.links.filter(l => !l.permiso || proxy.$auth.hasPermission(l.permiso)) }))
  .filter(g => g.links.length))

const fecha = new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
const hoy = fecha.charAt(0).toUpperCase() + fecha.slice(1)

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
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
.topbarInner { min-height: 70px; padding: 0 24px; gap: 10px }
.crumb b { display: block; font-size: 15px }
.crumb small { color: #6d8090 }
.user { display: flex; align-items: center; gap: 8px; padding-left: 8px }
.avatar {
  width: 35px; height: 35px; border-radius: 50%;
  display: grid; place-items: center;
  background: #e9eff5; color: #204664; font-weight: 900; font-size: 12px;
}
.utxt b { display: block; font-size: 11px }
.utxt small { display: block; color: #6d8090; font-size: 11px }
.btnSalir { background: #10263b; color: #fff; border-radius: 11px; font-weight: 800; font-size: 12px }

/* QDrawer pone la clase en .q-drawer__content, que no lleva el data-v del scope */
:deep(.sidebar) { background: linear-gradient(180deg, #0a223d, #0c3154); padding: 10px 9px; color: #d7e5ef }
.sideBrand { display: flex; align-items: center; gap: 9px; padding: 4px 5px 11px; border-bottom: 1px solid #ffffff12 }
.brandMark {
  width: 32px; height: 32px; border-radius: 9px;
  background: linear-gradient(145deg, #2c81c5, #0f4f85);
  display: grid; place-items: center; color: #fff;
}
.brandText b { color: #fff; font-size: 13px }
.brandText small { display: block; color: #9db5c8; font-size: 10px }
.navGroup { margin-top: 6px }
.navLabel { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; color: #7995aa; padding: 7px 8px 3px }
.navItem {
  display: flex; align-items: center; gap: 8px;
  color: #cbd9e4; padding: 6px 8px; border-radius: 8px; margin: 1px 0;
  font-size: 12.5px; line-height: 1.25; text-decoration: none;
}
.navItem:hover { background: #ffffff0e }
.navItem.active { background: #ffffff14; color: #fff; box-shadow: inset 3px 0 0 #ff4e5c }
.navIcon { width: 18px; text-align: center; font-size: 13px }
.sideFoot { border-top: 1px solid #ffffff12; margin-top: 10px; padding: 9px 6px; font-size: 10px; color: #8fa8bb; line-height: 1.45 }

@media (max-width: 720px) {
  .topbarInner { padding: 0 12px }
}
</style>
