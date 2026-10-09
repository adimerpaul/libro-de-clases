import { defineRouter } from '#q-app'
import { routes, handleHotUpdate } from 'vue-router/auto-routes'
import axios from 'axios'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'
import { Alert } from '../addons/Alert'
import { permissionForPath, hasPermission } from './permissions'

let permissionsRequest = null

// Permisos del usuario: primero la caché de localStorage (disponible al instante
// tras el login) y, si no existe, se consulta /me antes de resolver el guard.
async function getPermissions () {
  try {
    const cached = JSON.parse(localStorage.getItem('permissionsUrme') || '[]')
    if (cached.length) return cached
  } catch (e) { /* caché corrupta: se vuelve a pedir */ }

  if (!permissionsRequest) {
    const base = (import.meta.env.VITE_API_BACK || '').replace(/\/+$/, '')
    permissionsRequest = axios
      .get(base + '/me', {
        headers: { Authorization: `Bearer ${localStorage.getItem('tokenUrme')}` },
      })
      .then(res => {
        const perms = (res.data.permissions || []).map(p => p.name)
        localStorage.setItem('permissionsUrme', JSON.stringify(perms))
        return perms
      })
      .catch(() => [])
      .finally(() => { permissionsRequest = null })
  }

  return permissionsRequest
}

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
  })

  Router.beforeEach(async (to) => {
    if (to.path === '/login') return true
    if (to.path.startsWith('/verificacion/')) return true
    const token = localStorage.getItem('tokenUrme')
    if (!token) return '/login'

    const required = permissionForPath(to.path)
    if (!required) return true

    const permissions = await getPermissions()
    if (hasPermission(permissions, required)) return true

    Alert.error('No tiene permiso para acceder a esta sección')
    return '/'
  })

  if (import.meta.hot) {
    handleHotUpdate(Router)
  }

  return Router
})
