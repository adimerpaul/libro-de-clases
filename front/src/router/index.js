import { defineRouter } from '#q-app'
import { routes, handleHotUpdate } from 'vue-router/auto-routes'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'
import { TOKEN_KEY, useAuthStore } from '../stores/auth'

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
  })

  Router.beforeEach((to) => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (to.path === '/login') return token ? '/' : true
    if (!token) return '/login'
    // Páginas con definePage({ meta: { permiso } }) solo para quien lo tiene
    const auth = useAuthStore()
    if (to.meta.permiso && auth.isLogged && !auth.hasPermission(to.meta.permiso)) return '/'
    return true
  })

  if (import.meta.hot) {
    handleHotUpdate(Router)
  }

  return Router
})
