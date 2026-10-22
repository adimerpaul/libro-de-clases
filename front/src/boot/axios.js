import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { Alert } from '../addons/Alert'
import { useAuthStore, TOKEN_KEY } from '../stores/auth'

const api = axios.create({ baseURL: import.meta.env.VITE_API_BACK })

export default boot(async ({ app, router }) => {
  const auth = useAuthStore()

  app.config.globalProperties.$axios = api
  app.config.globalProperties.$alert = Alert
  app.config.globalProperties.$auth = auth
  app.config.globalProperties.$version = import.meta.env.VITE_VERSION

  api.interceptors.request.use(config => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
  })

  // Token vencido o revocado: se limpia la sesión y se vuelve al login
  api.interceptors.response.use(
    res => res,
    error => {
      if (error.response?.status === 401 && localStorage.getItem(TOKEN_KEY)) {
        auth.logout()
        router.push('/login')
      }
      return Promise.reject(error)
    },
  )

  // Se esperan los permisos antes de montar la app: el menú y el router dependen de ellos
  if (localStorage.getItem(TOKEN_KEY)) {
    await api.get('me').then(res => auth.setSesion(res.data)).catch(() => {})
  }
})

export { api }
